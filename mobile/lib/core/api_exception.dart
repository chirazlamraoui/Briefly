class ApiException implements Exception {
  ApiException(this.message, {this.statusCode, this.fieldErrors = const {}});

  final String message;
  final int? statusCode;
  final Map<String, List<String>> fieldErrors;

  String? firstFieldError(String field) {
    final errors = fieldErrors[field];
    if (errors == null || errors.isEmpty) {
      return null;
    }

    return errors.first;
  }

  @override
  String toString() => message;
}
