import 'package:briefly_mobile/models/models.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test('blocked status requires a blocker note', () {
    expect(isMissingBlockerNote('BLOCKED', ''), isTrue);
    expect(isMissingBlockerNote('BLOCKED', '   '), isTrue);
    expect(isMissingBlockerNote('BLOCKED', 'Waiting on API'), isFalse);
  });

  test('other statuses do not require a blocker note', () {
    expect(isMissingBlockerNote('TODO', ''), isFalse);
    expect(isMissingBlockerNote('IN_PROGRESS', ''), isFalse);
    expect(isMissingBlockerNote('DONE', ''), isFalse);
  });
}
