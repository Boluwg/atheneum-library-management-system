#include <Wire.h>
#include <Adafruit_PN532.h>

// I2C wiring: IRQ -> D2, RESET -> D3.
constexpr uint8_t PN532_IRQ = 2;
constexpr uint8_t PN532_RESET = 3;

Adafruit_PN532 nfc(PN532_IRQ, PN532_RESET);

void setup() {
  Serial.begin(9600);
  nfc.begin();

  const uint32_t version = nfc.getFirmwareVersion();
  if (!version) {
    Serial.println("PN532_NOT_FOUND");
    return;
  }

  nfc.SAMConfig();
  Serial.println("PN532_READY");
}

void loop() {
  uint8_t uid[7];
  uint8_t uidLength = 0;
  const bool found = nfc.readPassiveTargetID(
      PN532_MIFARE_ISO14443A,
      uid,
      &uidLength,
      100
  );

  if (!found) {
    delay(50);
    return;
  }

  for (uint8_t index = 0; index < uidLength; index++) {
    if (uid[index] < 0x10) {
      Serial.print('0');
    }
    Serial.print(uid[index], HEX);
  }
  Serial.println();
  delay(1200);
}
