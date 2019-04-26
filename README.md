# Soyal AR-727H TCPIP communication

a library for soyal AR-727H TCPIP comunication

## Installation

```bash
composer require oommgg/soyal
```

## Usage

```php
require __DIR__ . '/vendor/autoload.php';

use Oommgg\Soyal\Ar727;

$soyal = new Ar727('192.168.1.66', 1621);

$result = $soyal->getCard(2);
$result = $soyal->resetCards(0, 1);
$i = 0;
for ($i=0; $i < 16383; $i++) {
  $soyal->setCard($i, random_int(1, 65535), random_int(1, 65535));
}

$result = $soyal->getOldestLog();
$soyal->deleteOldestLog();
$result = $soyal->getTime();
$result = $soyal->setTime('2018-11-11 11:11:11');
$result = $soyal->setTime(); //set client local time
$result = $soyal->getStatus();
```

## License

This package is licensed under [The MIT License (MIT)](LICENSE).