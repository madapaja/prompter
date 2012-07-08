prompter
========

Symfony2 日本語ドキュメントの翻訳度を表示するためのちょっとしたツールです。

Usage
=====

```bash
# Symfony2オリジナルドキュメントを取得
git clone git://github.com/symfony/symfony-docs.git

# Symfony2日本語ドキュメントを取得
git://github.com/symfony-japan/symfony-docs-ja.git

# prompter を取得
git clone git://github.com/madapaja/prompter.git
cd prompter/

# composer で依存関係を解消
curl -s http://getcomposer.org/installer | php
php composer.phar install

# 実行
./prompter check ../symfony-docs ../symfony-docs-ja
```

License
=======

The MIT License (MIT)
---------------------

Copyright © 2012 IWASAKI Koji (@madapaja).

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
documentation files (the "Software"), to deal in the Software without restriction, including without limitation
the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software,
and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO
THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.