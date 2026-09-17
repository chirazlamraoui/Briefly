import 'dart:io';

import 'package:flutter/foundation.dart';

/// Where the phone should call Laravel (the same site as the browser).
///
/// A real iPhone cannot use 127.0.0.1 (that is the phone itself).
/// Pass the Mac Wi-Fi address:
/// `--dart-define=API_BASE_URL=http://10.x.x.x:8000`
class ApiConfig {
  static String get baseUrl {
    const fromEnv = String.fromEnvironment('API_BASE_URL');
    if (fromEnv.isNotEmpty) {
      return fromEnv;
    }

    if (!kIsWeb && Platform.isAndroid) {
      return 'http://10.0.2.2:8000';
    }

    return 'http://127.0.0.1:8000';
  }
}
