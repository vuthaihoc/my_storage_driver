# My Storage Driver

A Flysystem driver for my custom storage

# Requirements

- A server with code from another my project

# Usage

- Composer

```
    composer require vuthaihoc/flysystem-my-storage
```

- Add service provider

```
    Thikdev\LaFly\LaflyStorageProvider::class
```

- Config example

```
    'flystorage_demo' => [
        'driver'     => 'lafly',
        'host'       => 'http://localhost/flystorage/public',
        'username'   => 'judah.yundt@example.net',
        'password'   => 'secret',
        'connection' => 'local_001',
        'cdn' => 'http://localhost/flystorage/public',
    ],
```

