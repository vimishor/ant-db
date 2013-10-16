## AntDb [![Bitdeli Badge](https://d2weczhvl823v0.cloudfront.net/vimishor/ant-db/trend.png)](https://bitdeli.com/free "Bitdeli Badge")

Although mysql extension is deprecated starting with PHP 5.5.0 and many of the professionals recommend PDO as an
alternative, it's still heavily used ( _mostly by beginners_ ), because there are a lot of tutorials online targeting
this extension. The sad part is that some of the tutorials available online do not teach the beginner about SQL
injection, sanitizing user input and other best practices.

Because PDO and big wrappers like DBAL can be overwhelming for a beginner, I started writing this thin wrapper targeted
to beginners, which ( _hopefully_ ) will make their life easier and they will use it instead of mysql extension.

### Goals

- Keep it light and easy to use as possible.
- Keep the API similar as possible to [Doctrine DBAL](https://github.com/doctrine/dbal), so anyone can migrate easily.

### Who needs this ?

 - Any beginner that still uses `mysql_` extension.
 - Because the API is similar to Doctrine DBAL, this wrapper can be used by anyone who needs something small for
prototyping.

### Requirements

- PHP 5.3.2 or newer with `pdo_mysql` extension installed.

### Contributing

You must know the drill by now: fork, write awesome code and send pull request.

### License

AndDb is licensed under the MIT License - see the LICENSE file for details
