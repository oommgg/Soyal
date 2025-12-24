# Soyal AR-727H TCP/IP Communication Library

A PHP library for communicating with Soyal AR-727H access control readers via TCP/IP protocol.

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://www.php.net/)

## Requirements

- PHP 8.1 or higher
- nesbot/carbon ^3.0
- Network access to AR-727H device

## Installation

```bash
composer require oommgg/soyal
```

## Features

- 🔌 TCP/IP socket communication with AR-727H controllers
- 🎫 Card management (add, read, disable, reset)
- 📝 Event log retrieval and management
- ⏰ Device time synchronization
- 🔄 Device status monitoring and reboot
- ✅ Full checksum validation
- 🛡️ Type-safe with PHP 8.1+ features
- ⚠️ Comprehensive error handling

## Quick Start

```php
<?php
require __DIR__ . '/vendor/autoload.php';

use Oommgg\Soyal\Ar727;

// Connect to AR-727H device
$soyal = new Ar727('192.168.1.66', 1621, 0x01);

// Read card information
$card = $soyal->getCard(100);
print_r($card);
// Output:
// [
//     'address' => 100,
//     'uid1' => '46865',
//     'uid2' => '64318',
//     'status' => true,
//     'expired' => '2099-12-31'
// ]

// Add/Update card
$soyal->setCard(100, 12345, 67890);

// Disable card
$soyal->disableCard(100);

// Get device time
$time = $soyal->getTime();
echo "Device time: $time\n";

// Sync time with server
$soyal->setTime(); // Uses current time
// or
$soyal->setTime('2025-12-24 12:00:00');

// Get device status
$status = $soyal->getStatus();
print_r($status);

// Close connection
$soyal->disconnect();
```

## API Documentation

### Connection Management

#### `__construct(string $host, int $port = 1621, int $nodeId = 0x01)`

Creates a new connection to the AR-727H device.

#### `connect(int $timeout = 5): self`

Establishes socket connection with timeout.

#### `disconnect(): void`

Closes the socket connection.

### Card Management

#### `getCard(int $address): array`

Retrieves card information by address (0-16383).

**Returns:**

```php
[
    'address' => 100,      // Card address
    'uid1' => '12345',     // First UID (5-digit string)
    'uid2' => '67890',     // Second UID (5-digit string)
    'status' => true,      // Card enabled status
    'expired' => '2099-12-31' // Expiry date (null if invalid)
]
```

#### `setCard(int $address, int $uid1, int $uid2, bool $disable = false): self`

Sets or updates card information at specified address.

**Parameters:**

- `$address`: Card address (0-16383)
- `$uid1`: First UID (0-65535)
- `$uid2`: Second UID (0-65535)
- `$disable`: Set to `true` to disable the card

#### `disableCard(int $address): self`

Disables a card at the specified address.

#### `resetCards(int $start = 0, ?int $end = null): array`

Resets card data for a range of addresses.

### Event Log Management

#### `getOldestLog(): array`

Retrieves the oldest event log from device memory.

**Returns:**

```php
[
    'time' => '2025-12-24 12:30:45',
    'func_code' => 0x18,
    'address' => '100',
    'uid1' => '12345',
    'uid2' => '67890',
    'door' => 1,
    'type' => 1  // F1:1, F2:2, F3:3, F4:4
]
```

Returns empty array `[]` if no logs available.

#### `deleteOldestLog(): self`

Deletes the oldest event log from device memory.

**Example - Processing all logs:**

```php
while ($log = $soyal->getOldestLog()) {
    // Process log...
    processLog($log);

    // Must delete to read next log
    $soyal->deleteOldestLog();
}
```

### Time Synchronization

#### `getTime(): string`

Gets current device time.

**Returns:** `"2025-12-24 12:30:45"`

#### `setTime(string $time = ''): array`

Sets device time. Uses Asia/Taipei timezone.

**Parameters:**

- `$time`: Time string (e.g., "2025-12-24 12:00:00"). Empty = current time.

### Device Management

#### `getStatus(): array`

Gets device status information.

**Returns:**

```php
[
    'raw' => [...],        // Raw response data
    'node_id' => 1,        // Node ID
    'response_code' => 4   // Response code
]
```

#### `reboot(): array`

Reboots the device.

## Error Handling

The library throws two types of exceptions:

### `DeviceTimeOutException`

Thrown when connection to device fails or times out.

```php
use Oommgg\Soyal\Exceptions\DeviceTimeOutException;

try {
    $soyal = new Ar727('192.168.1.66');
} catch (DeviceTimeOutException $e) {
    echo "Connection failed: " . $e->getMessage();
}
```

### `DeviceErrorException`

Thrown when device returns an error or invalid data.

```php
use Oommgg\Soyal\Exceptions\DeviceErrorException;

try {
    $soyal->setCard(100, 12345, 67890);
} catch (DeviceErrorException $e) {
    echo "Operation failed: " . $e->getMessage();
}
```

## Advanced Usage

### Batch Card Operations

```php
// Add multiple cards
for ($i = 0; $i < 100; $i++) {
    try {
        $soyal->setCard($i, rand(10000, 99999), rand(10000, 99999));
        echo "Card $i added successfully\n";
    } catch (DeviceErrorException $e) {
        echo "Failed to add card $i: " . $e->getMessage() . "\n";
    }
}
```

### Event Log Processing

```php
$logs = [];
while ($log = $soyal->getOldestLog()) {
    $logs[] = $log;
    $soyal->deleteOldestLog();

    // Prevent infinite loop
    if (count($logs) > 1000) break;
}

// Process logs
foreach ($logs as $log) {
    echo sprintf(
        "[%s] User %s (UID1: %s) accessed Door %d\n",
        $log['time'],
        $log['address'],
        $log['uid1'],
        $log['door']
    );
}
```

### Connection Pooling

```php
class SoyalPool
{
    private array $connections = [];

    public function getConnection(string $host): Ar727
    {
        if (!isset($this->connections[$host])) {
            $this->connections[$host] = new Ar727($host);
        }
        return $this->connections[$host];
    }

    public function closeAll(): void
    {
        foreach ($this->connections as $conn) {
            $conn->disconnect();
        }
        $this->connections = [];
    }
}
```

## Protocol Details

This library implements the Extended Protocol format as specified in the AR-727H documentation:

- **Header**: `0xFF 0x00 0x5A 0xA5`
- **Checksums**: XOR and SUM validation
- **Max packet size**: 1024 bytes (TCP/IP), 1018 bytes (RS485)

For more details, see the [protocol documentation](doc/Protocol_881E_725Ev2_82xEv5%204V05.pdf).

## Testing

```bash
# Requires physical AR-727H device connected
php test.php
```

Modify `test.php` with your device's IP address and port before running.

## Changelog

### Version 2.0.0 (2025-12-24)

- ⚠️ **Breaking Change**: Requires PHP 8.1+
- ✨ Upgraded to Carbon v3
- 🔧 Fixed checksum validation bug (critical)
- 📝 Added proper type declarations for all properties and methods
- 🛡️ Enhanced error handling with proper exception throwing
- 📚 Improved PHPDoc comments with detailed examples
- ✅ Added comprehensive checksum verification tests
- 🎯 Improved date parsing with validation
- 🔄 Better status parsing with structured response

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This package is licensed under [The MIT License (MIT)](LICENSE).

## Credits

- **Author**: Ben Chung
- **Email**: <oommgg@gmail.com>
- **Protocol**: Soyal AR-727H TCP/IP Communication Protocol

## Support

For issues and questions:

- 📝 [Create an issue](https://github.com/oommgg/Soyal/issues)
- 📧 Email: <oommgg@gmail.com>
