---
name: running-flutter-iphone
description: >-
  Installs Xcode and CocoaPods, pairs a physical iPhone, enables Developer Mode,
  signs the Flutter iOS app, and runs Briefly on device against the local Laravel
  server. Use when running the Flutter app on an iPhone, installing Xcode or
  CocoaPods, enabling Developer Mode, fixing code signing, USB Trust, the iOS 14+
  black debug splash, wireless debugging, or pointing the phone at a LAN API URL.
---

# Running Flutter on a physical iPhone

App lives in `mobile/`. Prefer USB over wireless. Do not use the iOS Simulator unless the user asks.

## First-time Mac setup

1. CocoaPods: `brew install cocoapods` (CLI `pod`).
2. Xcode: `mas install 497799835` needs a sudo password, so open the App Store instead:

   ```bash
   open "macappstore://apps.apple.com/app/xcode/id497799835"
   ```

   Have the user click **Get** / **Install**. Wait until `/Applications/Xcode.app` exists.
3. First-launch component picker: keep **iOS** checked even if they will not use a simulator. That is the iPhone SDK. Leave watchOS off. macOS is already built-in.
4. Point CLT at Xcode if needed:

   ```bash
   sudo xcode-select --switch /Applications/Xcode.app/Contents/Developer
   sudo xcodebuild -runFirstLaunch
   ```

## Pair the iPhone (Developer Mode)

Developer Mode is **hidden** until Xcode starts pairing. USB Trust alone is not enough.

1. Unlock the phone, plug USB, tap **Trust**.
2. Open Xcode → **Window → Devices and Simulators** (or Device Hub) → select the iPhone.
3. First pair copies shared cache symbols. Leave the phone plugged in and unlocked. Do not cancel.
4. On the iPhone: **Settings → Privacy & Security**, scroll to **Security**. Turn on **Developer Mode** → Restart → **Enable** + passcode.
5. After the first signed install, trust the developer: **Settings → General → VPN & Device Management**.
6. If iOS asks for **Local Network**, tap **Allow**.

Confirm:

```bash
xcrun devicectl list devices
flutter devices
```

Expect the phone as an `ios` device. Wireless-only listing is slower and less reliable.

## Code signing

`DEVELOPMENT_TEAM` must be set. A free Apple ID is enough for the owner's iPhone.

1. `open mobile/ios/Runner.xcworkspace`
2. Select **Runner** project → **Runner** target → **Signing & Capabilities**.
3. **Automatically manage signing**.
4. **Team → Add an Account…** (Apple ID), then select that team.

Do not hardcode a team ID. After Xcode saves, `project.pbxproj` contains `DEVELOPMENT_TEAM` and `security find-identity -v -p codesigning` shows an `Apple Development:` identity.

If `flutter run` says "No valid code signing certificates", the team is missing. Do not retry until it is selected.

## Laravel must be reachable from the phone

`127.0.0.1` and `localhost` do not work on a physical device. Android emulator `10.0.2.2` does not apply.

```bash
ipconfig getifaddr en0
# stop any serve bound only to 127.0.0.1:8000
php artisan serve --host=0.0.0.0 --port=8000
```

Always pass the LAN origin:

```bash
cd mobile
flutter run -d <device-id> --dart-define=API_BASE_URL=http://<LAN_IP>:8000
```

`ApiConfig` in `mobile/lib/core/api_config.dart` defaults to `127.0.0.1:8000` without that define.

Phone and Mac must be on the same Wi‑Fi. `EnsureCanonicalAppUrl` already skips JSON/Bearer clients, so the LAN host is not 301'd to `APP_URL`.

`mobile/ios/Runner/Info.plist` must keep:

- `NSAllowsLocalNetworking` = true
- `NSLocalNetworkUsageDescription`
- `NSBonjourServices` → `_dartVmService._tcp`

## Debug vs release (black screen)

On iOS 14+, a **debug** build opened from the home screen or Recents shows:

> In iOS 14+, debug mode Flutter apps can only be launched from Flutter tooling…

That is expected. Do **not** have the user tap the icon or swipe the app away while `flutter run` is installing.

| Goal | Command |
| --- | --- |
| Hot reload, launched by tooling | `flutter run` (debug). Leave the session running. USB. |
| Tap the icon like a normal app | `flutter run --release` with the same `--dart-define`. |

If debug launch says the Dart VM Service was not discovered, the phone is probably wireless. Plug USB and retry, or switch to `--release`.

If the user swipes the app away during install, the process exits `0` and a later home-screen tap shows the black debug splash. Re-run from the CLI; do not "try again" from Recents.

## After it is running

Demo logins (password `password`):

- `admin@briefly.test`
- `marcus.chen@briefly.test`

Tell the user to leave the release session running, keep the same Wi‑Fi, and open **Briefly Mobile** from the home screen if they dismissed it.
