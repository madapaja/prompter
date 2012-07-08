<?php

namespace Madapaja\PrompterBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\DialogHelper;

use Symfony\Component\Finder\Finder;

use Madapaja\PrompterBundle\Repository\GitRepository;
use Madapaja\PrompterBundle\Repository\SymfonyDocsJaRepository;
use Madapaja\PrompterBundle\Repository\CompareFileInfo;

class CheckCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('check')
            ->setDescription('翻訳状況を確認します')
            ->setHelp('Usage: <info>./prompter check -s modified ../symfony-docs ../symfony-docs-ja</info>')
            ->addArgument('src', InputArgument::REQUIRED, 'オリジナルドキュメントのGitレポジトリ内のディレクトリ/ファイル')
            ->addArgument('dest', InputArgument::REQUIRED, '日本語ドキュメントのディレクトリ/ファイル')
            ->addOption('sort', 's', InputOption::VALUE_OPTIONAL,
            '並び替えのモードを指定します' . PHP_EOL
                . '<info>modified</info>: オリジナルの更新日時の新しい順' . PHP_EOL
                . '<info>filename</info>: ファイル名順' . PHP_EOL
                . '<info>status</info>: 翻訳度順（翻訳完了 > 更新が必要 > 不明 > オリジナルが無い > 未翻訳）' . PHP_EOL
                . '<info>status-desc</info>: 未翻訳度順（未翻訳 > オリジナルが無い > 不明 > 更新が必要 > 翻訳完了）' . PHP_EOL
                . '<info>no-sort</info>: 並び替えません' . PHP_EOL
            , 'modified')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $src = $input->getArgument('src');
        $dest = $input->getArgument('dest');
        $sortType = $input->getOption('sort');

        // ouput format の設定
        $output->getFormatter()->setStyle('sync', new OutputFormatterStyle('green'));
        $output->getFormatter()->setStyle('out', new OutputFormatterStyle('yellow'));
        $output->getFormatter()->setStyle('unknown', new OutputFormatterStyle('blue', null, array('underscore')));
        $output->getFormatter()->setStyle('notfound', new OutputFormatterStyle('red', null, array('bold')));
        $output->getFormatter()->setStyle('bold', new OutputFormatterStyle(null, null, array('bold')));

        // base dir
        $srcDirBase = false;
        if (is_dir($src)) {
            $srcDirBase = $src;
        } else if (is_file($src)) {
            $srcDirBase = dirname($src);
        }

        $destDirBase = false;
        if (is_dir($dest)) {
            $destDirBase = $dest;
        } else if (is_file($dest)) {
            $destDirBase = dirname($dest);
        }

        // init repository
        $srcRepository = new GitRepository($srcDirBase);
        $destRepository = new SymfonyDocsJaRepository($destDirBase);

        if (is_dir($src) && is_dir($dest)) {
            // check source files
            $srcFiles = array();
            foreach ((new Finder())->files()->in($src) as $file) {
                $fileInfo = $srcRepository->getFileInfo($file->getRealpath());
                $srcFiles[$fileInfo->pathname] = $fileInfo;
            }

            // check destination files
            $destFiles = array();
            foreach ((new Finder())->files()->in($dest) as $file) {
                $fileInfo = $destRepository->getFileInfo($file->getRealpath());
                $destFiles[$fileInfo->pathname] = $fileInfo;
            }

            // compare
            $files = new \ArrayObject();
            foreach ($srcFiles as $srcPathname => $srcFileInfo) {
                $destFileInfo = isset($destFiles[$srcPathname]) ? $destFiles[$srcPathname] : null;
                $files[$srcPathname] = (new CompareFileInfo($srcFileInfo, $destFileInfo))->compare();

                if (isset($destFiles[$srcPathname])) {
                    unset($destFiles[$srcPathname]);
                }
            }
            unset($srcFiles);

            foreach ($destFiles as $destPathname => $destFileInfo) {
                $files[$destPathname] = (new CompareFileInfo(null, $destFileInfo))->compare();
            }
            unset($destFiles);

            // sort TODO: model 内で処理？ソートの責任は自分が持つべきじゃない
            switch ($sortType) {
                case 'filename':
                    $files->uasort(function ($compereA, $compereB) {
                        $a = $compereA->src ? $compereA->src->pathname : $compereA->dest->pathname;
                        $b = $compereB->src ? $compereB->src->pathname : $compereB->dest->pathname;

                        return strnatcasecmp($a, $b);
                    });
                    break;

                case 'status':
                    $files->uasort(function ($compereA, $compereB) {
                        $a = $compereA->status;
                        $b = $compereB->status;

                        if ($a == $b) {
                            return 0;
                        }

                        return $a > $b ? 1 : -1;
                    });
                    break;

                case 'status-desc':
                    $files->uasort(function ($compereA, $compereB) {
                        $a = $compereA->status;
                        $b = $compereB->status;

                        if ($a == $b) {
                            return 0;
                        }

                        return $a > $b ? -1 : 1;
                    });
                    break;

                case 'no-sort':
                    break;

                case 'modified';
                default:
                    $files->uasort(function ($compereA, $compereB) {
                        $a = $compereA->src ? $compereA->src->modified : null;
                        $b = $compereB->src ? $compereB->src->modified : null;

                        if (!$a && !$b) {
                            return 0;
                        }

                        if (!$a) {
                            return 1;
                        }

                        if (!$b) {
                            return -1;
                        }

                        $a = $a->getTimestamp();
                        $b = $b->getTimestamp();

                        if ($a == $b) {
                            return 0;
                        }

                        return $a > $b ? -1 : 1;
                    });
                    break;
            }

            // output TODO: view 分けたい...
            $output->writeln(sprintf('source: <info>%s</info>', $src));
            $output->writeln(sprintf('destination: <info>%s</info>', $dest));
            $output->writeln('');

            $output->writeln('         source              destination');
            $output->writeln('     hash       date       hash       date    filename');
            $output->writeln('  ---------- ---------- ---------- ---------- --------');

            foreach ($files as $compere) {
                switch ($compere->status) {
                    case CompareFileInfo::SYNCHRONIZED:
                        $output->writeln(sprintf(
                            '  %-10s %-10s %-10s %-10s %s',
                            $compere->src->getVersion(10),
                            $compere->src->modified->format('Y-m-d'),
                            $compere->dest->getVersion(10),
                            $compere->dest->modified->format('Y-m-d'),
                            $compere->src->pathname
                        ));
                        break;

                    case CompareFileInfo::OUTDATED:
                        $output->writeln(sprintf(
                            '<out>*</out> %-10s %-10s <out>%-10s %-10s</out> <out>%s</out>',
                            $compere->src->getVersion(10),
                            $compere->src->modified->format('Y-m-d'),
                            $compere->dest->getVersion(10),
                            $compere->dest->modified->format('Y-m-d'),
                            $compere->src->pathname
                        ));
                        break;

                    case CompareFileInfo::UNKNOWN:
                        $destDate = $compere->dest->modified instanceof \DateTime
                            ? $compere->dest->modified->format('Y-m-d')
                            : '';

                        if (!$compere->src->version) {
                            $output->writeln(sprintf(
                                '<unknown>?</unknown> <unknown>%-10s %-10s</unknown> %-10s %-10s <unknown>%s</unknown>',
                                $compere->src->getVersion(10),
                                $compere->src->modified->format('Y-m-d'),
                                $compere->dest->getVersion(10) ?: '[  ????  ]',
                                $destDate ?: '[  ????  ]',
                                $compere->src->pathname
                            ));
                        } else {
                            $output->writeln(sprintf(
                                '<unknown>?</unknown> %-10s %-10s <unknown>%-10s %-10s</unknown> <unknown>%s</unknown>',
                                $compere->src->getVersion(10),
                                $compere->src->modified->format('Y-m-d'),
                                $compere->dest->getVersion(10) ?: '[  ????  ]',
                                $destDate ?: '[  ????  ]',
                                $compere->src->pathname
                            ));
                        }
                        break;

                    case CompareFileInfo::NO_SRC:
                        $destDate = $compere->dest->modified instanceof \DateTime
                            ? $compere->dest->modified->format('Y-m-d')
                            : '';

                        $output->writeln(sprintf(
                            '<notfound>!</notfound> <notfound>%21s</notfound> %-10s %-10s <notfound>%s</notfound>',
                            '  [ FILE NOT FOUND ] ',
                            $compere->dest->getVersion(10) ?: '[  ????  ]',
                            $destDate ?: '[  ????  ]',
                            $compere->dest->pathname
                        ));
                        break;

                    case CompareFileInfo::NO_DEST:
                        $output->writeln(sprintf(
                            '<notfound>!</notfound> %-10s %-10s <notfound>%21s</notfound> <notfound>%s</notfound>',
                            $compere->src->getVersion(10),
                            $compere->src->modified->format('Y-m-d'),
                            '  [ FILE NOT FOUND ] ',
                            $compere->src->pathname
                        ));
                        break;

                    default:
                        throw new \Exception();
                        break;
                }
            }
       } else {
            $srcFile = $src;
            $destFile = $dest;

            if (is_file($srcFile) && is_dir($destFile)) {
                if (substr($destFile, -1) != '/') {
                    $destFile = $destFile.'/';
                }

                $destFile = $destFile.basename($srcFile);
            } else if (is_dir($srcFile) && is_file($destFile)) {
                if (substr($srcFile, -1) != '/') {
                    $srcFile = $srcFile.'/';
                }

                $srcFile = $srcFile.basename($destFile);
            }

            if (!is_file($srcFile)) {
                throw new \Exception('src file not found: '.$srcFile);
            }

            if (!is_file($destFile)) {
                throw new \Exception('dest file not found: '.$destFile);
            }

            // compare
            $compere = (new CompareFileInfo(
                $srcRepository->getFileInfo($srcFile),
                $destRepository->getFileInfo($destFile)
            ))->compare();

            // output
            $currentDir = getcwd();
            $output->writeln(sprintf(
                'source: <info>%s</info>',
                strpos($compere->src->filename, $currentDir) === 0
                    ? substr($compere->src->filename, strlen($currentDir) + 1)
                    : $compere->src->filename
            ));
            $output->writeln(sprintf(
                'destination: <info>%s</info>',
                strpos($compere->dest->filename, $currentDir) === 0
                    ? substr($compere->dest->filename, strlen($currentDir) + 1)
                    : $compere->dest->filename
            ));
            $output->writeln('');

            $destDate = $compere->dest->modified instanceof \DateTime
                ? $compere->dest->modified->format('Y-m-d')
                : '';

            switch ($compere->status) {
                case CompareFileInfo::SYNCHRONIZED:
                    $output->writeln(sprintf('status: <sync>%s</sync>', 'Synchronized'));
                    break;

                case CompareFileInfo::OUTDATED:
                    $output->writeln(sprintf('status: <out>%s</out>', 'Outdated'));
                    break;

                case CompareFileInfo::UNKNOWN:
                    if (!$compere->src->version) {
                        $output->writeln(sprintf('status: <unknown>%s</unknown>', 'Source Unknown'));
                    } else {
                        $output->writeln(sprintf('status: <unknown>%s</unknown>', 'Destination Unknown'));
                    }
                    break;

                default:
                    throw new \Exception();
                    break;
            }

            $output->writeln('');
            $output->writeln(sprintf(
                'source status: %s (%s)',
                $compere->src->getVersion() ?: 'UNKNOWN',
                $compere->src->modified->format('Y-m-d H:i:s O')
            ));
            $output->writeln(sprintf(
                'destination: %s (%s)',
                $compere->dest->getVersion() ?: 'UNKNOWN',
                $destDate ?: 'UNKNOWN'
            ));

            if ($compere->status == CompareFileInfo::SYNCHRONIZED) {
                return true;
            }

            $output->writeln('');

            /** @var $dialog DialogHelper  */
            $dialog = $this->getHelperSet()->get('dialog');

            if ($compere->status === CompareFileInfo::OUTDATED) {
                if (!$dialog->askConfirmation($output, '<question>View source diff?</question> [<bold>Y</bold>/n]: ')) {
                    return;
                }

                // output diff
                $output->writeln('');
                echo $compere->getDiff();
                return true;
            }

            if (!$dialog->askConfirmation($output, '<question>View source?</question> [<bold>Y</bold>/n]: ')) {
                return;
            }

            // output diff
            $output->writeln('');
            echo $compere->getSource();
        }
    }
}