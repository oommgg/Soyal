<?php

namespace Oommgg\Soyal;

use Carbon\Carbon;
use Oommgg\Soyal\Exceptions\DeviceErrorException;
use Oommgg\Soyal\Exceptions\DeviceTimeOutException;

class Ar727
{
    public const ACK = 4;
    public const NACK = 5;

    protected string $host;
    protected int $port;
    protected int $nodeId;

    /** @var resource|false */
    protected $fp;

    /**
     * construct
     *
     * @param string $host
     * @param integer $port
     * @param integer $nodeId
     */
    public function __construct(string $host, int $port = 1621, int $nodeId = 0x01)
    {
        $this->host = $host;
        $this->port = $port;
        $this->nodeId = $nodeId;
        $this->connect();
    }

    /**
     * connect socket
     *
     * @param int $timeout
     * @return self
     */
    public function connect(int $timeout = 5): self
    {
        $this->fp = @fsockopen($this->host, $this->port, $errno, $errstr, $timeout);
        if (!$this->fp) {
            throw new DeviceTimeOutException("$errstr ($errno)", $errno);
        }

        stream_set_blocking($this->fp, true);

        return $this;
    }

    /**
     * disconnect socket
     *
     * @return void
     */
    public function disconnect(): void
    {
        fclose($this->fp);
    }

    /**
     * receive data from socket
     *
     * @return array<int, int>
     */
    protected function receive(): array
    {
        $buffer = fread($this->fp, 65535);

        if (empty($buffer)) {
            throw new DeviceErrorException('Node error: can not get node data.');
        }

        return unpack('C*', $buffer, 0);
    }

    /**
     * 取得卡機狀態
     *
     * @return array
     */
    public function getStatus(): array
    {
        $packed = pack('C*', ...$this->newExtPack(0x18));
        fwrite($this->fp, $packed, strlen($packed));
        $result = $this->receive();

        if ($this->checksum($result) == self::NACK) {
            throw new DeviceErrorException('Error on getting device status');
        }

        return $result;
    }

    /**
     * Get card info by card number
     *
     * Result array indexes:
     *   14-15: uid1 (high/low bytes, e.g., 0xB711 => 46865)
     *   16-17: uid2 (high/low bytes, e.g., 0xFB3E => 64318)
     *   22: status (88: enabled, 0: disabled)
     *   26-28: expiry date (year/month/day offset from 2000)
     *
     * @param int $address Card address (0-16383)
     * @return array<string, mixed>
     */
    public function getCard(int $address): array
    {
        $_address = unpack('C*', pack('S', $address), 0);
        $packed = pack('C*', ...$this->newExtPack(0x87, [$_address[2], $_address[1], 0x01]));
        fwrite($this->fp, $packed, strlen($packed));
        $result = $this->receive();

        $uid1 = $this->parseUid($result[14], $result[15]);
        $uid2 = $this->parseUid($result[16], $result[17]);
        $status = $result[22] > 0;

        // 解析過期日期 (以 2000 年為基準)
        $year = 2000 + ($result[26] % 100);
        $month = $result[27] % 100;
        $day = $result[28] % 100;

        // 檢查日期有效性,避免如 month=0 或 day=0 的無效值
        $expired = null;
        if ($month > 0 && $month <= 12 && $day > 0 && $day <= 31) {
            try {
                $time = Carbon::create($year, $month, $day);
                $expired = $time->toDateString();
            } catch (\Exception $e) {
                // 無效日期時保持 null
            }
        }

        return [
            'address' => $address,
            'uid1' => $uid1,
            'uid2' => $uid2,
            'status' => $status,
            'expired' => $expired,
        ];
    }

    /**
     * Set card info
     *
     * Status byte (0b01011000 = 88): card enabled
     * Status byte 0: card disabled
     *
     * @param int $address Card address (0-16383)
     * @param int $uid1 First UID
     * @param int $uid2 Second UID
     * @param bool $disable Whether to disable the card
     * @return self
     */
    public function setCard(int $address, int $uid1, int $uid2, bool $disable = false): self
    {
        $_address = unpack('C*', pack('S', $address), 0);
        $tag1 = unpack('C*', pack('S', $uid1), 0);
        $tag2 = unpack('C*', pack('S', $uid2), 0);
        $status = $disable ? 0 : 88; // 0b01011000 => 88

        $packed = pack('C*', ...$this->newExtPack(0x84, [
            1,              // record number to set
            $_address[2],   // user address HIGH bit
            $_address[1],   // user address LOW bit
            0, 0, 0, 0,
            $tag1[2],       // uid1 HIGH bit
            $tag1[1],       // uid1 LOW bit
            $tag2[2],       // uid2 HIGH bit
            $tag2[1],       // uid2 LOW bit
            0, 0, 0, 0,     // pin (4 bytes)
            $status,        // mode: 0 for disable, 88 for enable
            0,              // zone
            0xFF,           // group1
            0xFF,           // group2
            99,             // year (offset from 2000)
            12,             // month
            31,             // day
            0,              // level
            0, 0, 0, 0
        ]));

        fwrite($this->fp, $packed, strlen($packed));
        $result = $this->receive();

        if ($this->checksum($result) == self::NACK) {
            throw new DeviceErrorException('Error on setting card');
        }

        return $this;
    }

    /**
     * disable card
     *
     * @param integer $address
     * @return self
     */
    public function disableCard(int $address): self
    {
        $this->setCard($address, 65535, 65535, true);
        return $this;
    }

    /**
     * reset cards
     *
     * @param integer $start
     * @param integer|null $end
     * @return array
     */
    public function resetCards(int $start = 0, ?int $end = null): array
    {
        $tag1 = unpack('C*', pack('S', $start), 0);
        $endAddress = $end ?? ($start + 1);
        $tag2 = unpack('C*', pack('S', $endAddress), 0);

        $packed = pack('C*', ...$this->newExtPack(0x85, [$tag1[2], $tag1[1], $tag2[2], $tag2[1]]));
        fwrite($this->fp, $packed, strlen($packed));
        $result = $this->receive();

        if ($this->checksum($result) == self::NACK) {
            throw new DeviceErrorException('Error on resetting cards');
        }

        return $result;
    }

    /**
     * reboot device
     *
     * @return array
     */
    public function reboot(): array
    {
        $packed = pack('C*', ...$this->newExtPack(0xA6, [0xFD]));
        fwrite($this->fp, $packed, strlen($packed));
        return $this->receive();
    }

    /**
     * 取得卡機時間
     *
     * @return string
     */
    public function getTime(): string
    {
        $packed = pack('C*', ...$this->newExtPack(0x24));
        fwrite($this->fp, $packed, strlen($packed));
        $result = $this->receive();

        if ($this->checksum($result) == self::NACK) {
            throw new DeviceErrorException('Error on getting device time');
        }

        try {
            $time = Carbon::create(
                2000 + $result[16],
                $result[15],
                $result[14],
                $result[12],
                $result[11],
                $result[10]
            );
            return $time->toDateTimeString();
        } catch (\Exception $e) {
            throw new DeviceErrorException('Invalid time data received from device: ' . $e->getMessage());
        }
    }

    /**
     * 設定卡機時間
     *
     * @param string $time
     * @return array
     */
    public function setTime(string $time = ''): array
    {
        $now = $time ? Carbon::parse($time, 'Asia/Taipei') : Carbon::now('Asia/Taipei');
        $packed = pack('C*', ...$this->newExtPack(0x23, [
            $now->second, $now->minute, $now->hour, $now->dayOfWeek + 1, $now->day, $now->month, $now->year % 100,
        ]));
        fwrite($this->fp, $packed, strlen($packed));
        $result = $this->receive();

        if ($this->checksum($result) == self::NACK) {
            throw new DeviceErrorException('Error on setting device time');
        }

        return $result;
    }

    /**
     * 取得卡機記憶體中最舊的 event log
     *
     * @return array
     */
    public function getOldestLog(): array
    {
        $packed = pack('C*', ...$this->newExtPack(0x25));
        fwrite($this->fp, $packed, strlen($packed));
        $result = $this->receive();
        $code = $this->checksum($result);

        print_r($result);
        // 沒有任何記錄了
        if ($code == self::ACK) {
            return [];
        }

        try {
            $time = Carbon::create(
                2000 + $result[16] % 100,
                $result[15] % 100,
                $result[14] % 100,
                $result[12] % 100,
                $result[11] % 100,
                $result[10] % 100
            );
        } catch (\Exception $e) {
            throw new DeviceErrorException('Invalid log time data: ' . $e->getMessage());
        }

        $funcCode = $this->getFunctionCode($result);
        $address = $this->parseUid($result[18], $result[19]);
        $uid1 = $this->parseUid($result[24], $result[25]);
        $uid2 = $this->parseUid($result[28], $result[29]);
        $door = $result[26];

        /**
         * F1: 0, F2: 32, F3: 64, F4: 96
         */
        $type = $result[20] > 0 ? intval($result[20] / 32) + 1 : 1; // F1:1, F2:2, F3:3, F4:4

        return [
            'time' => $time->toDateTimeString(),
            'func_code' => $funcCode,
            'address' => $address,
            'uid1' => $uid1,
            'uid2' => $uid2,
            'door' => $door,
            'type' => $type,
        ];
    }

    /**
     * 刪除卡機記憶體中最舊的 event log
     *
     * @return self
     */
    public function deleteOldestLog(): self
    {
        $packed = pack('C*', ...$this->newExtPack(0x37));
        fwrite($this->fp, $packed, strlen($packed));
        $result = $this->receive();
        if ($this->checksum($result) == self::NACK) {
            throw new DeviceErrorException('Error on deleting event log');
        }

        return $this;
    }

    /**
     * extend packet data
     *
     * @param integer $command
     * @param array $data
     * @return array
     */
    protected function newExtPack(int $command, array $data = []): array
    {
        $length = 4;
        $xor = 0xFF ^ $this->nodeId ^ $command;
        $sum = $this->nodeId + $command;

        if ($data) {
            $length += count($data);
            foreach ($data as $d) {
                $xor ^= $d;
                $sum += $d;
            }
        }

        $xor = $xor % 256;
        $sum += $xor;
        $sum = $sum % 256;
        $length2 = unpack('C*', pack('L', $length), 0);
        $buffer = [0xFF, 0x00, 0x5A, 0xA5, $length2[2], $length2[1], $this->nodeId, $command];

        if ($data) {
            array_push($buffer, ...$data);
        }

        array_push($buffer, $xor);
        array_push($buffer, $sum);

        return $buffer;
    }

    /**
     * Checksum validation for received data
     *
     * Validates both XOR and SUM checksums according to protocol
     *
     * @param array<int, int> $data Received data buffer
     * @return int Response code (ACK/NACK) or -1 if checksum invalid
     */
    protected function checksum(array $data): int
    {
        $isExtended = $data[1] === 0xFF;
        $start = $isExtended ? 7 : 3;
        $code = $isExtended ? $data[8] : $data[4];

        $xor = 0xFF;
        $sum = 0;
        $lastDataIndex = count($data) - 2;  // Last data byte index (before checksums)

        // Process all data bytes from start to lastDataIndex (inclusive)
        for ($i = $start; $i <= $lastDataIndex; $i++) {
            $xor ^= $data[$i];
            $sum += $data[$i];
        }

        $xor %= 256;
        $sum = ($sum + $xor) % 256;

        // Verify checksums
        if ($data[$lastDataIndex + 1] !== $xor || $data[$lastDataIndex + 2] !== $sum) {
            return -1;
        }

        return $code;
    }

    /**
     * Parse UID from two bytes (high and low)
     *
     * Converts two hex bytes to a 5-digit decimal string
     * Example: high=0xB7, low=0x11 => 0xB711 => 46865 => "46865"
     *
     * @param int $high High byte
     * @param int $low Low byte
     * @return string 5-digit UID string
     */
    protected function parseUid(int $high, int $low): string
    {
        $hexString = sprintf("%02x%02x", $high, $low);
        $decimal = hexdec($hexString);
        return sprintf("%05d", $decimal);
    }

    /**
     * Extract function code from response data
     *
     * Handles both extended (0xFF prefix) and standard protocol formats
     *
     * @param array<int, int> $data Response data
     * @return int Function code
     */
    protected function getFunctionCode(array $data): int
    {
        return $data[1] === 0xFF ? $data[8] : $data[4];
    }
}
