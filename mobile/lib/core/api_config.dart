import 'dart:io';

import 'package:flutter/foundation.dart';

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
