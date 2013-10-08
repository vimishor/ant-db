# AntDb usage

AntDb has a very light API, that can le learned very fast. It has two "main" methods `write()`, `read()` and a few
"helper" methods for common used statements like inserting and updating. To learn more about each method, please read
bellow.

### Configure

Configuration is very straightforward.

```
$config = array(
    'host' => '127.0.0.1',
    'user' => 'user',
    'pass' => 'pass',
    'name' => 'dbname'
);

$db = new Gentle\AntDb\AntDb($config);

// check connection
if (!$db->isConnected()) {
    echo "Can't connect to MySQL: ". $db->getLastError();
}
```

Now that we are connected, we can create a table for out very important data.

### Custom write query

`write()` method can be used for statements which don't return data, like insert, delete, update, etc. For the start,
let't create a table to work with:

```
$result = $db->write('
CREATE TABLE users (
    id int(11) NOT NULL AUTO_INCREMENT,
    username VARCHAR(255),
    password VARCHAR(255),
    email VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (`id`)
) charset=utf8');

if ($result !== false) {
    echo 'Table created!';
}
```

### Insert

After we created out shiny table, let's insert some data. For this we can use the `insert()` method.

```
$result = $db->insert('users', array(
    'username'  => 'nancy',
    'password'  => 'nancy_pass',
    'email'     => 'doe@example.com'
));

if ($db->hasError()) {
    echo "ERROR : ". $db->getLastError();
} else {
    echo $rows .' new rows have been added. <br>';
}
```

### Update

To update data in a table, we can use the `update()` method, which has the following sintax: Using the data already in
the table, let's change nancy's password to "secret" and her email address to "nancy@example.com":

```
$result = $db->update(
    'users',
    array('password' => 'secret', 'email' => 'nancy@example.com'),
    array('username' => 'nancy')
);
```

### Fetch one result

Let's see if nancy's password has been updated.

```
$user = $db->fetchAssoc('select * from users where username = ?', array('nancy'));
echo $user['password']; // will output "secret"
```

### Fetch all results

```
$users = $db->read('select * from users where email LIKE ?', array('%@example.com'))->fetchAll();
var_dump($users);
```
