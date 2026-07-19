import 'package:flutter/material.dart';
import 'api_client.dart';
import 'auth_service.dart';
import 'login_page.dart';

class HomePage extends StatefulWidget { const HomePage({super.key}); @override State<HomePage> createState()=>_HomePageState(); }
class _HomePageState extends State<HomePage> {
  List<dynamic> products=[]; List<dynamic> orders=[]; String? error;
  @override void initState(){super.initState();load();}
  Future<void> load() async { try{final dio=await ApiClient.instance.dio; final p=await dio.get('/catalog/products'); final o=await dio.get('/buyer/orders'); setState((){products=p.data['data']['data']??[];orders=o.data['data']['data']??[];error=null;});}catch(e){setState(()=>error=e.toString());} }
  Future<void> logout() async{await AuthService().logout();if(!mounted)return;Navigator.of(context).pushAndRemoveUntil(MaterialPageRoute(builder:(_)=>const LoginPage(title:'IAS Market Buyer',role:'buyer',deviceName:'IAS Market Buyer Android')),(_)=>false);}
  @override Widget build(BuildContext context)=>Scaffold(appBar:AppBar(title:const Text('IAS Market Buyer'),actions:[IconButton(onPressed:logout,icon:const Icon(Icons.logout))]),body:RefreshIndicator(onRefresh:load,child:ListView(padding:const EdgeInsets.all(16),children:[if(error!=null)Text(error!,style:const TextStyle(color:Colors.red)),Text('Produk tersedia',style:Theme.of(context).textTheme.titleLarge),...products.take(10).map((p)=>Card(child:ListTile(title:Text('${p['name']}'),subtitle:Text('${p['store']?['name']??''}'),trailing:Text('Rp ${p['price']}')))),const SizedBox(height:16),Text('Pesanan saya',style:Theme.of(context).textTheme.titleLarge),...orders.take(10).map((o)=>Card(child:ListTile(title:Text('${o['order_number']}'),subtitle:Text('${o['status']}'),trailing:Text('Rp ${o['total']}'))))])));
}
