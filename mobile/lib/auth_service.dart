import 'package:dio/dio.dart';
import 'api_client.dart';

class AuthService {
  String? _extractToken(Map<String, dynamic> json) {
    final direct = json['token'] ?? json['access_token'];
    if (direct is String && direct.isNotEmpty) return direct;
    final data = json['data'];
    if (data is Map) {
      final nested = data['token'] ?? data['access_token'];
      if (nested is String && nested.isNotEmpty) return nested;
    }
    return null;
  }

  Future<void> login({required String login, required String password}) async {
    final dio = await ApiClient.instance.dio;
    final response = await dio.post('/login', data: {
      'login': login.trim(),
      'password': password,
      'device_name': 'eArsip Android',
    });
    final json = Map<String, dynamic>.from(response.data as Map);
    final token = _extractToken(json);
    if (token == null) {
      throw Exception(json['message'] ?? 'Server tidak mengirim token autentikasi.');
    }
    await ApiClient.storage.write(key: 'auth_token', value: token);
  }

  Future<bool> hasSession() async => (await ApiClient.storage.read(key: 'auth_token'))?.isNotEmpty == true;

  Future<void> logout() async {
    try {
      final dio = await ApiClient.instance.dio;
      await dio.post('/logout');
    } on DioException catch (_) {}
    await ApiClient.storage.delete(key: 'auth_token');
  }
}
