import 'dart:io';
import 'package:dio/dio.dart';
import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'api_client.dart';

class UploadArchivePage extends StatefulWidget {
  const UploadArchivePage({super.key});
  @override
  State<UploadArchivePage> createState() => _UploadArchivePageState();
}

class _UploadArchivePageState extends State<UploadArchivePage> {
  final title = TextEditingController();
  final documentNumber = TextEditingController();
  final subject = TextEditingController();
  String type = 'GENERAL';
  int? classificationId;
  File? file;
  List<dynamic> classifications = [];
  bool loading = false;
  String? message;

  @override
  void initState() { super.initState(); loadClassifications(); }

  Future<void> loadClassifications() async {
    final dio = await ApiClient.instance.dio;
    final response = await dio.get('/classifications');
    if (!mounted) return;
    setState(() {
      classifications = List<dynamic>.from(response.data['data'] as List);
      if (classifications.isNotEmpty) classificationId = classifications.first['id'] as int;
    });
  }

  Future<void> pickFile() async {
    final result = await FilePicker.platform.pickFiles(type: FileType.custom, allowedExtensions: ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx']);
    final path = result?.files.single.path;
    if (path != null) setState(() => file = File(path));
  }

  Future<void> submit() async {
    if (classificationId == null || title.text.trim().isEmpty) {
      setState(() => message = 'Judul dan klasifikasi wajib diisi.');
      return;
    }
    setState(() { loading = true; message = null; });
    try {
      final form = FormData.fromMap({
        'classification_id': classificationId,
        'type': type,
        'title': title.text.trim(),
        'document_number': documentNumber.text.trim(),
        'subject': subject.text.trim(),
        'security_level': 'INTERNAL',
        'status': 'PENDING',
        if (file != null) 'file': await MultipartFile.fromFile(file!.path, filename: file!.uri.pathSegments.last),
      });
      final dio = await ApiClient.instance.dio;
      final response = await dio.post('/archives', data: form);
      if (!mounted) return;
      setState(() => message = response.data['message']?.toString() ?? 'Arsip berhasil dibuat.');
    } catch (e) {
      if (mounted) setState(() => message = e.toString());
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Registrasi Arsip')),
      body: ListView(padding: const EdgeInsets.all(16), children: [
        DropdownButtonFormField<String>(value: type, decoration: const InputDecoration(labelText: 'Jenis arsip', border: OutlineInputBorder()), items: const [DropdownMenuItem(value: 'INCOMING', child: Text('Surat Masuk')), DropdownMenuItem(value: 'OUTGOING', child: Text('Surat Keluar')), DropdownMenuItem(value: 'GENERAL', child: Text('Arsip Umum'))], onChanged: (v) => setState(() => type = v ?? 'GENERAL')),
        const SizedBox(height: 12),
        DropdownButtonFormField<int>(value: classificationId, decoration: const InputDecoration(labelText: 'Klasifikasi', border: OutlineInputBorder()), items: classifications.map((item) => DropdownMenuItem<int>(value: item['id'] as int, child: Text('${item['code']} - ${item['name']}'))).toList(), onChanged: (v) => setState(() => classificationId = v)),
        const SizedBox(height: 12),
        TextField(controller: title, decoration: const InputDecoration(labelText: 'Judul arsip', border: OutlineInputBorder())),
        const SizedBox(height: 12),
        TextField(controller: documentNumber, decoration: const InputDecoration(labelText: 'Nomor dokumen', border: OutlineInputBorder())),
        const SizedBox(height: 12),
        TextField(controller: subject, maxLines: 3, decoration: const InputDecoration(labelText: 'Perihal/keterangan', border: OutlineInputBorder())),
        const SizedBox(height: 12),
        OutlinedButton.icon(onPressed: pickFile, icon: const Icon(Icons.attach_file), label: Text(file == null ? 'Pilih dokumen' : file!.uri.pathSegments.last)),
        if (message != null) Padding(padding: const EdgeInsets.symmetric(vertical: 12), child: Text(message!)),
        FilledButton(onPressed: loading ? null : submit, child: loading ? const CircularProgressIndicator() : const Text('Simpan Arsip')),
      ]),
    );
  }
}
