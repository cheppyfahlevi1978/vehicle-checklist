#!/usr/bin/env bash
set -euo pipefail

ROOT="$(pwd)"
BUILD="$ROOT/.build-dejiaohui"
RELEASE="$ROOT/release"
rm -rf "$BUILD" "$RELEASE"
mkdir -p "$BUILD" "$RELEASE"

composer create-project laravel/laravel:^13.0 "$BUILD/server" --no-interaction --prefer-dist
cd "$BUILD/server"

rm -f database/migrations/*.php
mkdir -p database/migrations/central database/migrations/tenant database/tenants
mkdir -p app/Services app/Http/Middleware app/Http/Controllers resources/views/layouts resources/views/auth resources/views/national resources/views/resources public/assets

cat > .env.example <<'ENV'
APP_NAME="SIYADI Dejiaohui Indonesia"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://yayasan.ias4u.my.id
APP_TIMEZONE=Asia/Jakarta
APP_LOCALE=id
APP_FALLBACK_LOCALE=en

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=central
DB_CENTRAL_DRIVER=sqlite
DB_CENTRAL_DATABASE=
DEFAULT_BRANCH_CODE=SBY

SESSION_DRIVER=file
SESSION_LIFETIME=240
CACHE_STORE=file
QUEUE_CONNECTION=sync

MAIL_MAILER=log
MAIL_HOST=103.77.78.76
MAIL_PORT=587
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_SCHEME=null
MAIL_FROM_ADDRESS="noreply@dejiaohui.id"
MAIL_FROM_NAME="${APP_NAME}"
ENV

cat > config/database.php <<'PHP'
<?php

use Illuminate\Support\Str;

$centralDatabase = env('DB_CENTRAL_DATABASE') ?: database_path('central.sqlite');

return [
    'default' => env('DB_CONNECTION', 'central'),
    'connections' => [
        'central' => env('DB_CENTRAL_DRIVER', 'sqlite') === 'sqlite'
            ? [
                'driver' => 'sqlite',
                'url' => null,
                'database' => $centralDatabase,
                'prefix' => '',
                'foreign_key_constraints' => true,
                'busy_timeout' => null,
                'journal_mode' => null,
                'synchronous' => null,
            ]
            : [
                'driver' => env('DB_CENTRAL_DRIVER', 'mysql'),
                'url' => null,
                'host' => env('DB_CENTRAL_HOST', '127.0.0.1'),
                'port' => env('DB_CENTRAL_PORT', '3306'),
                'database' => env('DB_CENTRAL_DATABASE', 'dejiaohui_central'),
                'username' => env('DB_CENTRAL_USERNAME', 'root'),
                'password' => env('DB_CENTRAL_PASSWORD', ''),
                'unix_socket' => env('DB_CENTRAL_SOCKET', ''),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'prefix_indexes' => true,
                'strict' => true,
                'engine' => null,
            ],
        'tenant' => [
            'driver' => 'sqlite',
            'url' => null,
            'database' => database_path('tenants/placeholder.sqlite'),
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
    ],
    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],
    'redis' => [
        'client' => env('REDIS_CLIENT', 'phpredis'),
        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'siyadi'), '_').'_database_'),
        ],
        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],
    ],
];
PHP

cat > config/siyadi.php <<'PHP'
<?php

return [
    'modules' => [
        'members' => [
            'title' => 'Anggota', 'singular' => 'Anggota', 'table' => 'members', 'icon' => '👥',
            'search' => ['member_no', 'name', 'phone'],
            'columns' => ['member_no', 'name', 'phone', 'status'],
            'fields' => [
                'member_no' => ['label' => 'Nomor Anggota', 'type' => 'text', 'rules' => ['required','string','max:50']],
                'name' => ['label' => 'Nama Lengkap', 'type' => 'text', 'rules' => ['required','string','max:150']],
                'chinese_name' => ['label' => 'Nama Tionghoa', 'type' => 'text', 'rules' => ['nullable','string','max:150']],
                'phone' => ['label' => 'Telepon', 'type' => 'text', 'rules' => ['nullable','string','max:30']],
                'address' => ['label' => 'Alamat', 'type' => 'textarea', 'rules' => ['nullable','string']],
                'join_date' => ['label' => 'Tanggal Bergabung', 'type' => 'date', 'rules' => ['nullable','date']],
                'status' => ['label' => 'Status', 'type' => 'select', 'options' => ['Aktif','Tidak Aktif'], 'rules' => ['required','string']],
            ],
        ],
        'donations' => [
            'title' => 'Donasi', 'singular' => 'Donasi', 'table' => 'donations', 'icon' => '💝',
            'search' => ['receipt_no', 'donor_name', 'purpose'],
            'columns' => ['receipt_no', 'donor_name', 'donation_date', 'amount', 'status'],
            'fields' => [
                'receipt_no' => ['label' => 'Nomor Kuitansi', 'type' => 'text', 'rules' => ['required','string','max:50']],
                'donor_name' => ['label' => 'Nama Donatur', 'type' => 'text', 'rules' => ['required','string','max:150']],
                'donation_date' => ['label' => 'Tanggal', 'type' => 'date', 'rules' => ['required','date']],
                'amount' => ['label' => 'Nominal', 'type' => 'number', 'rules' => ['required','numeric','min:0']],
                'payment_method' => ['label' => 'Metode', 'type' => 'select', 'options' => ['Tunai','Transfer Bank','QRIS','Lainnya'], 'rules' => ['required','string']],
                'purpose' => ['label' => 'Tujuan Donasi', 'type' => 'text', 'rules' => ['nullable','string','max:150']],
                'status' => ['label' => 'Status', 'type' => 'select', 'options' => ['Menunggu Verifikasi','Terverifikasi','Dibatalkan'], 'rules' => ['required','string']],
            ],
        ],
        'programs' => [
            'title' => 'Program Sosial', 'singular' => 'Program', 'table' => 'social_programs', 'icon' => '🌏',
            'search' => ['program_code', 'name', 'category', 'location'],
            'columns' => ['program_code', 'name', 'start_date', 'location', 'status'],
            'fields' => [
                'program_code' => ['label' => 'Kode Program', 'type' => 'text', 'rules' => ['required','string','max:50']],
                'name' => ['label' => 'Nama Program', 'type' => 'text', 'rules' => ['required','string','max:150']],
                'category' => ['label' => 'Kategori', 'type' => 'select', 'options' => ['Pembagian Sembako','Donor Darah','Pendidikan Moral','Bantuan Bencana','Pemeriksaan Kesehatan','Pelestarian Budaya','Lainnya'], 'rules' => ['required','string']],
                'start_date' => ['label' => 'Tanggal Mulai', 'type' => 'date', 'rules' => ['required','date']],
                'location' => ['label' => 'Lokasi', 'type' => 'text', 'rules' => ['nullable','string','max:190']],
                'target_beneficiaries' => ['label' => 'Target Penerima', 'type' => 'number', 'rules' => ['nullable','integer','min:0']],
                'budget' => ['label' => 'Anggaran', 'type' => 'number', 'rules' => ['nullable','numeric','min:0']],
                'status' => ['label' => 'Status', 'type' => 'select', 'options' => ['Direncanakan','Berjalan','Selesai','Dibatalkan'], 'rules' => ['required','string']],
                'description' => ['label' => 'Deskripsi', 'type' => 'textarea', 'rules' => ['nullable','string']],
            ],
        ],
        'beneficiaries' => [
            'title' => 'Penerima Manfaat', 'singular' => 'Penerima', 'table' => 'beneficiaries', 'icon' => '🫶',
            'search' => ['beneficiary_no', 'name', 'need_type'],
            'columns' => ['beneficiary_no', 'name', 'need_type', 'verification_status'],
            'fields' => [
                'beneficiary_no' => ['label' => 'Nomor Penerima', 'type' => 'text', 'rules' => ['required','string','max:50']],
                'name' => ['label' => 'Nama', 'type' => 'text', 'rules' => ['required','string','max:150']],
                'phone' => ['label' => 'Telepon', 'type' => 'text', 'rules' => ['nullable','string','max:30']],
                'address' => ['label' => 'Alamat', 'type' => 'textarea', 'rules' => ['nullable','string']],
                'need_type' => ['label' => 'Jenis Kebutuhan', 'type' => 'text', 'rules' => ['required','string','max:150']],
                'verification_status' => ['label' => 'Verifikasi', 'type' => 'select', 'options' => ['Belum Disurvei','Disurvei','Terverifikasi','Ditolak'], 'rules' => ['required','string']],
            ],
        ],
        'finances' => [
            'title' => 'Keuangan', 'singular' => 'Transaksi', 'table' => 'finances', 'icon' => '💰',
            'search' => ['transaction_no', 'category', 'description'],
            'columns' => ['transaction_no', 'transaction_date', 'type', 'category', 'amount'],
            'fields' => [
                'transaction_no' => ['label' => 'Nomor Transaksi', 'type' => 'text', 'rules' => ['required','string','max:50']],
                'transaction_date' => ['label' => 'Tanggal', 'type' => 'date', 'rules' => ['required','date']],
                'type' => ['label' => 'Jenis', 'type' => 'select', 'options' => ['Pemasukan','Pengeluaran'], 'rules' => ['required','string']],
                'category' => ['label' => 'Kategori', 'type' => 'text', 'rules' => ['required','string','max:150']],
                'description' => ['label' => 'Keterangan', 'type' => 'textarea', 'rules' => ['required','string']],
                'amount' => ['label' => 'Nominal', 'type' => 'number', 'rules' => ['required','numeric','min:0']],
            ],
        ],
    ],
];
PHP

cat > app/Services/TenantManager.php <<'PHP'
<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use stdClass;

class TenantManager
{
    public function resolve(?string $code, string $host): ?stdClass
    {
        try {
            $query = DB::connection('central')->table('branches')->where('is_active', true);
            $byDomain = (clone $query)->where('domain', strtolower($host))->first();
            if ($byDomain) return $byDomain;
            return $code ? $query->whereRaw('UPPER(code) = ?', [strtoupper($code)])->first() : null;
        } catch (\Throwable) {
            return null;
        }
    }

    public function configure(stdClass $branch): void
    {
        $database = $branch->database_name;
        if (! str_starts_with($database, DIRECTORY_SEPARATOR)) $database = base_path($database);

        Config::set('database.connections.tenant', [
            'driver' => 'sqlite', 'url' => null, 'database' => $database,
            'prefix' => '', 'foreign_key_constraints' => true,
        ]);
        DB::purge('tenant');
        DB::connection('tenant')->getPdo();
        app()->instance('tenant.branch', $branch);
    }
}
PHP

cat > app/Http/Middleware/ResolveTenant.php <<'PHP'
<?php

namespace App\Http\Middleware;

use App\Services\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    public function __construct(private readonly TenantManager $tenants) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('national*') || $request->is('up')) return $next($request);

        $code = $request->query('branch') ?: session('branch_code') ?: env('DEFAULT_BRANCH_CODE');
        $branch = $this->tenants->resolve($code, strtolower($request->getHost()));

        if (! $branch) {
            return response('Database belum terpasang. Jalankan: php artisan system:install-demo --force', 503);
        }

        $this->tenants->configure($branch);
        session(['branch_code' => $branch->code]);
        return $next($request);
    }
}
PHP

cat > app/Http/Middleware/TenantAuthenticated.php <<'PHP'
<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
class TenantAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $branch = app('tenant.branch');
        $user = session('tenant_user');
        if (! $user || ($user['branch_code'] ?? null) !== $branch->code) return redirect()->route('login');
        return $next($request);
    }
}
PHP

cat > app/Http/Middleware/NationalAuthenticated.php <<'PHP'
<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
class NationalAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        return session('national_user') ? $next($request) : redirect()->route('national.login');
    }
}
PHP

cat > bootstrap/app.php <<'PHP'
<?php

use App\Http\Middleware\NationalAuthenticated;
use App\Http\Middleware\ResolveTenant;
use App\Http\Middleware\TenantAuthenticated;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(web: __DIR__.'/../routes/web.php', commands: __DIR__.'/../routes/console.php', health: '/up')
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('web', [ResolveTenant::class]);
        $middleware->alias(['tenant.auth' => TenantAuthenticated::class, 'national.auth' => NationalAuthenticated::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {})
    ->create();
PHP

cat > app/Http/Controllers/AuthController.php <<'PHP'
<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
class AuthController extends Controller
{
    public function show() { return view('auth.login'); }
    public function login(Request $request)
    {
        $data = $request->validate(['email' => ['required','email'], 'password' => ['required','string']]);
        $user = DB::connection('tenant')->table('users')->where('email', strtolower($data['email']))->where('is_active', true)->first();
        if (! $user || ! Hash::check($data['password'], $user->password)) return back()->withInput()->with('error','Email atau password tidak sesuai.');
        $branch = app('tenant.branch');
        $request->session()->regenerate();
        session(['tenant_user' => ['id'=>$user->id,'name'=>$user->name,'role'=>$user->role,'branch_code'=>$branch->code]]);
        return redirect()->route('dashboard');
    }
    public function logout(Request $request)
    {
        $request->session()->forget('tenant_user');
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
PHP

cat > app/Http/Controllers/NationalAuthController.php <<'PHP'
<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
class NationalAuthController extends Controller
{
    public function show() { return view('national.login'); }
    public function login(Request $request)
    {
        $data = $request->validate(['email' => ['required','email'], 'password' => ['required','string']]);
        $user = DB::connection('central')->table('national_users')->where('email', strtolower($data['email']))->where('is_active', true)->first();
        if (! $user || ! Hash::check($data['password'], $user->password)) return back()->withInput()->with('error','Email atau password nasional tidak sesuai.');
        $request->session()->regenerate();
        session(['national_user' => ['id'=>$user->id,'name'=>$user->name,'role'=>$user->role]]);
        return redirect()->route('national.dashboard');
    }
    public function logout(Request $request)
    {
        $request->session()->forget('national_user');
        $request->session()->regenerateToken();
        return redirect()->route('national.login');
    }
}
PHP

cat > app/Http/Controllers/DashboardController.php <<'PHP'
<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
class DashboardController extends Controller
{
    public function index()
    {
        $db = DB::connection('tenant');
        $stats = [
            'members'=>$db->table('members')->where('status','Aktif')->count(),
            'programs'=>$db->table('social_programs')->count(),
            'beneficiaries'=>$db->table('beneficiaries')->count(),
            'donations'=>(float)$db->table('donations')->where('status','Terverifikasi')->sum('amount'),
            'income'=>(float)$db->table('finances')->where('type','Pemasukan')->sum('amount'),
            'expense'=>(float)$db->table('finances')->where('type','Pengeluaran')->sum('amount'),
        ];
        $stats['balance']=$stats['income']-$stats['expense'];
        return view('dashboard', compact('stats'));
    }
}
PHP

cat > app/Http/Controllers/ResourceController.php <<'PHP'
<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class ResourceController extends Controller
{
    private function def(string $module): array { $d=config("siyadi.modules.$module"); abort_unless(is_array($d),404); return $d; }
    public function index(Request $request,string $module)
    {
        $definition=$this->def($module); $q=DB::connection('tenant')->table($definition['table']);
        if($s=trim((string)$request->query('q'))){$q->where(function($b)use($definition,$s){foreach($definition['search'] as $i=>$f){$m=$i?'orWhere':'where';$b->{$m}($f,'like',"%$s%");}});} 
        $rows=$q->orderByDesc('id')->simplePaginate(15)->withQueryString(); return view('resources.index',compact('module','definition','rows'));
    }
    public function create(string $module){$definition=$this->def($module);$row=null;return view('resources.form',compact('module','definition','row'));}
    public function store(Request $r,string $module){$d=$this->def($module);$data=$r->validate($this->rules($d));$data['created_at']=now();$data['updated_at']=now();DB::connection('tenant')->table($d['table'])->insert($data);return redirect()->route('resources.index',$module)->with('success','Data berhasil ditambahkan.');}
    public function edit(string $module,int $id){$definition=$this->def($module);$row=DB::connection('tenant')->table($definition['table'])->find($id);abort_unless($row,404);return view('resources.form',compact('module','definition','row'));}
    public function update(Request $r,string $module,int $id){$d=$this->def($module);$data=$r->validate($this->rules($d));$data['updated_at']=now();DB::connection('tenant')->table($d['table'])->where('id',$id)->update($data);return redirect()->route('resources.index',$module)->with('success','Data berhasil diperbarui.');}
    public function destroy(string $module,int $id){$d=$this->def($module);DB::connection('tenant')->table($d['table'])->where('id',$id)->delete();return back()->with('success','Data berhasil dihapus.');}
    private function rules(array $d):array{$r=[];foreach($d['fields'] as $f=>$m)$r[$f]=$m['rules']??['nullable'];return $r;}
}
PHP

cat > app/Http/Controllers/NationalController.php <<'PHP'
<?php
namespace App\Http\Controllers;
use App\Services\TenantManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
class NationalController extends Controller
{
    public function dashboard(){ $branches=DB::connection('central')->table('branches')->orderBy('name')->get(); return view('national.dashboard',compact('branches')); }
    public function store(Request $request,TenantManager $tenants)
    {
        $data=$request->validate(['code'=>['required','string','max:10'],'name'=>['required','string','max:150'],'city'=>['nullable','string','max:100'],'province'=>['nullable','string','max:100']]);
        $code=strtoupper($data['code']);$relative='database/tenants/'.strtolower($code).'.sqlite';$absolute=base_path($relative);File::ensureDirectoryExists(dirname($absolute));if(!File::exists($absolute))File::put($absolute,'');
        $id=DB::connection('central')->table('branches')->insertGetId(['code'=>$code,'name'=>$data['name'],'city'=>$data['city']??null,'province'=>$data['province']??null,'domain'=>null,'database_name'=>$relative,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $branch=DB::connection('central')->table('branches')->find($id);$tenants->configure($branch);
        Artisan::call('migrate',['--database'=>'tenant','--path'=>'database/migrations/tenant','--force'=>true]);
        DB::connection('tenant')->table('users')->insert(['name'=>'Administrator '.$data['name'],'email'=>'admin.'.strtolower($code).'@dejiaohui.id','password'=>Hash::make('Cabang123!'),'role'=>'Administrator Cabang','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        return back()->with('success','Cabang dibuat. Login awal: admin.'.strtolower($code).'@dejiaohui.id / Cabang123!');
    }
}
PHP

cat > routes/web.php <<'PHP'
<?php
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NationalAuthController;
use App\Http\Controllers\NationalController;
use App\Http\Controllers\ResourceController;
use Illuminate\Support\Facades\Route;
Route::get('/',fn()=>redirect()->route('dashboard'));
Route::get('/login',[AuthController::class,'show'])->name('login');
Route::post('/login',[AuthController::class,'login'])->name('login.submit');
Route::post('/logout',[AuthController::class,'logout'])->name('logout');
Route::middleware('tenant.auth')->group(function(){
    Route::get('/dashboard',[DashboardController::class,'index'])->name('dashboard');
    Route::get('/data/{module}',[ResourceController::class,'index'])->name('resources.index');
    Route::get('/data/{module}/create',[ResourceController::class,'create'])->name('resources.create');
    Route::post('/data/{module}',[ResourceController::class,'store'])->name('resources.store');
    Route::get('/data/{module}/{id}/edit',[ResourceController::class,'edit'])->name('resources.edit');
    Route::put('/data/{module}/{id}',[ResourceController::class,'update'])->name('resources.update');
    Route::delete('/data/{module}/{id}',[ResourceController::class,'destroy'])->name('resources.destroy');
});
Route::prefix('national')->name('national.')->group(function(){
    Route::get('/login',[NationalAuthController::class,'show'])->name('login');Route::post('/login',[NationalAuthController::class,'login'])->name('login.submit');Route::post('/logout',[NationalAuthController::class,'logout'])->name('logout');
    Route::middleware('national.auth')->group(function(){Route::get('/dashboard',[NationalController::class,'dashboard'])->name('dashboard');Route::post('/branches',[NationalController::class,'store'])->name('branches.store');});
});
PHP

cat > routes/console.php <<'PHP'
<?php
use App\Services\TenantManager;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
Artisan::command('system:install-demo {--force}',function(){
    File::ensureDirectoryExists(database_path('tenants'));File::ensureDirectoryExists(storage_path('framework/cache/data'));File::ensureDirectoryExists(storage_path('framework/sessions'));File::ensureDirectoryExists(storage_path('framework/views'));
    $central=config('database.connections.central.database');if(!File::exists($central))File::put($central,'');
    Artisan::call('migrate',['--database'=>'central','--path'=>'database/migrations/central','--force'=>true],$this->output);
    DB::connection('central')->table('national_users')->updateOrInsert(['email'=>'admin@dejiaohui.id'],['name'=>'Administrator Nasional','password'=>Hash::make('Admin123!'),'role'=>'Super Admin Nasional','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
    $rel='database/tenants/sby.sqlite';$abs=base_path($rel);if(!File::exists($abs))File::put($abs,'');
    DB::connection('central')->table('branches')->updateOrInsert(['code'=>'SBY'],['name'=>'Yayasan Dejiaohui Surabaya','city'=>'Surabaya','province'=>'Jawa Timur','domain'=>null,'database_name'=>$rel,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
    $branch=DB::connection('central')->table('branches')->where('code','SBY')->first();app(TenantManager::class)->configure($branch);
    Artisan::call('migrate',['--database'=>'tenant','--path'=>'database/migrations/tenant','--force'=>true],$this->output);
    DB::connection('tenant')->table('users')->updateOrInsert(['email'=>'admin.sby@dejiaohui.id'],['name'=>'Administrator Cabang Surabaya','password'=>Hash::make('Cabang123!'),'role'=>'Administrator Cabang','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
    if(DB::connection('tenant')->table('members')->count()===0){DB::connection('tenant')->table('members')->insert(['member_no'=>'SBY-AGT-000001','name'=>'Anggota Contoh','chinese_name'=>null,'phone'=>'081234567890','address'=>'Surabaya','join_date'=>now()->format('Y-m-d'),'status'=>'Aktif','created_at'=>now(),'updated_at'=>now()]);}
    $this->info('Instalasi selesai. Cabang: admin.sby@dejiaohui.id / Cabang123! | Nasional: admin@dejiaohui.id / Admin123!');
})->purpose('Memasang database pusat dan cabang pilot Surabaya.');
PHP

cat > database/migrations/central/2026_01_01_000001_create_central_tables.php <<'PHP'
<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\Schema;
return new class extends Migration{public function up():void{Schema::connection('central')->create('national_users',function(Blueprint $t){$t->id();$t->string('name');$t->string('email')->unique();$t->string('password');$t->string('role');$t->boolean('is_active')->default(true);$t->timestamps();});Schema::connection('central')->create('branches',function(Blueprint $t){$t->id();$t->string('code',10)->unique();$t->string('name');$t->string('city')->nullable();$t->string('province')->nullable();$t->string('domain')->nullable()->unique();$t->string('database_name');$t->boolean('is_active')->default(true);$t->timestamps();});}public function down():void{Schema::connection('central')->dropIfExists('branches');Schema::connection('central')->dropIfExists('national_users');}};
PHP

cat > database/migrations/tenant/2026_01_01_000001_create_tenant_tables.php <<'PHP'
<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\Schema;
return new class extends Migration{public function up():void{
Schema::connection('tenant')->create('users',function(Blueprint $t){$t->id();$t->string('name');$t->string('email')->unique();$t->string('password');$t->string('role');$t->boolean('is_active')->default(true);$t->timestamps();});
Schema::connection('tenant')->create('members',function(Blueprint $t){$t->id();$t->string('member_no')->unique();$t->string('name');$t->string('chinese_name')->nullable();$t->string('phone')->nullable();$t->text('address')->nullable();$t->date('join_date')->nullable();$t->string('status');$t->timestamps();});
Schema::connection('tenant')->create('donations',function(Blueprint $t){$t->id();$t->string('receipt_no')->unique();$t->string('donor_name');$t->date('donation_date');$t->decimal('amount',18,2);$t->string('payment_method');$t->string('purpose')->nullable();$t->string('status');$t->timestamps();});
Schema::connection('tenant')->create('social_programs',function(Blueprint $t){$t->id();$t->string('program_code')->unique();$t->string('name');$t->string('category');$t->date('start_date');$t->string('location')->nullable();$t->unsignedInteger('target_beneficiaries')->nullable();$t->decimal('budget',18,2)->nullable();$t->string('status');$t->text('description')->nullable();$t->timestamps();});
Schema::connection('tenant')->create('beneficiaries',function(Blueprint $t){$t->id();$t->string('beneficiary_no')->unique();$t->string('name');$t->string('phone')->nullable();$t->text('address')->nullable();$t->string('need_type');$t->string('verification_status');$t->timestamps();});
Schema::connection('tenant')->create('finances',function(Blueprint $t){$t->id();$t->string('transaction_no')->unique();$t->date('transaction_date');$t->string('type');$t->string('category');$t->text('description');$t->decimal('amount',18,2);$t->timestamps();});
}public function down():void{foreach(['finances','beneficiaries','social_programs','donations','members','users'] as $x)Schema::connection('tenant')->dropIfExists($x);}};
PHP

cat > resources/views/layouts/app.blade.php <<'BLADE'
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#741f2f"><title>@yield('title','SIYADI')</title><link rel="stylesheet" href="/assets/app.css"></head><body><div class="shell"><aside><div class="brand"><b>德</b><span>SIYADI<small>Dejiaohui Indonesia</small></span></div><div class="branch">{{ app('tenant.branch')->name }}</div><nav><a href="{{route('dashboard')}}">🏠 Dashboard</a>@foreach(config('siyadi.modules') as $k=>$m)<a href="{{route('resources.index',$k)}}">{{$m['icon']}} {{$m['title']}}</a>@endforeach</nav></aside><main><header><div><strong>@yield('page-title','Dashboard')</strong><small>{{session('tenant_user.name')}}</small></div><form method="POST" action="{{route('logout')}}">@csrf<button>Keluar</button></form></header><section class="content">@if(session('success'))<div class="ok">{{session('success')}}</div>@endif @if(session('error'))<div class="err">{{session('error')}}</div>@endif @if($errors->any())<div class="err">{{$errors->first()}}</div>@endif @yield('content')</section></main></div></body></html>
BLADE

cat > resources/views/auth/login.blade.php <<'BLADE'
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Login SIYADI</title><link rel="stylesheet" href="/assets/app.css"></head><body class="login"><form class="login-card" method="POST" action="{{route('login.submit')}}">@csrf<div class="seal">德</div><h1>Yayasan Dejiaohui</h1><p>{{app('tenant.branch')->name}}</p>@if(session('error'))<div class="err">{{session('error')}}</div>@endif<input type="email" name="email" placeholder="Email" value="{{old('email')}}" required><input type="password" name="password" placeholder="Password" required><button class="primary">Masuk Cabang</button><a href="{{route('national.login')}}">Portal Nasional</a></form></body></html>
BLADE

cat > resources/views/dashboard.blade.php <<'BLADE'
@extends('layouts.app') @section('title','Dashboard SIYADI') @section('page-title','Dashboard Cabang') @section('content')<div class="hero"><div><small>YAYASAN DEJIAOHUI INDONESIA</small><h1>Kebajikan, pelayanan sosial, dan transparansi dalam satu sistem.</h1></div><b>善</b></div><div class="stats"><article><span>Anggota Aktif</span><strong>{{number_format($stats['members'])}}</strong></article><article><span>Program</span><strong>{{number_format($stats['programs'])}}</strong></article><article><span>Penerima Manfaat</span><strong>{{number_format($stats['beneficiaries'])}}</strong></article><article><span>Total Donasi</span><strong>Rp{{number_format($stats['donations'],0,',','.')}}</strong></article><article><span>Saldo</span><strong>Rp{{number_format($stats['balance'],0,',','.')}}</strong></article></div><div class="panel"><h2>Versi Pilot Cabang</h2><p>Kelola anggota, program sosial, penerima manfaat, donasi, dan keuangan. Setiap cabang menggunakan database tersendiri.</p></div>@endsection
BLADE

cat > resources/views/resources/index.blade.php <<'BLADE'
@extends('layouts.app') @section('title',$definition['title']) @section('page-title',$definition['title']) @section('content')<div class="actions"><form><input name="q" value="{{request('q')}}" placeholder="Cari..."><button>Cari</button></form><a class="primary" href="{{route('resources.create',$module)}}">+ Tambah</a></div><div class="panel table"><table><thead><tr>@foreach($definition['columns'] as $c)<th>{{$definition['fields'][$c]['label']}}</th>@endforeach<th>Aksi</th></tr></thead><tbody>@forelse($rows as $row)<tr>@foreach($definition['columns'] as $c)<td>@if(in_array($c,['amount','budget']))Rp{{number_format((float)$row->{$c},0,',','.')}}@else{{$row->{$c}}}@endif</td>@endforeach<td><a href="{{route('resources.edit',[$module,$row->id])}}">Ubah</a><form class="inline" method="POST" action="{{route('resources.destroy',[$module,$row->id])}}">@csrf @method('DELETE')<button onclick="return confirm('Hapus data?')">Hapus</button></form></td></tr>@empty<tr><td colspan="20">Belum ada data.</td></tr>@endforelse</tbody></table></div>@endsection
BLADE

cat > resources/views/resources/form.blade.php <<'BLADE'
@extends('layouts.app') @section('title',$row?'Ubah Data':'Tambah Data') @section('page-title',$row?'Ubah '.$definition['singular']:'Tambah '.$definition['singular']) @section('content')<form class="panel form" method="POST" action="{{$row?route('resources.update',[$module,$row->id]):route('resources.store',$module)}}">@csrf @if($row)@method('PUT')@endif <div class="grid">@foreach($definition['fields'] as $f=>$m)<label>{{$m['label']}}@php($v=old($f,$row->{$f}??'')) @if($m['type']==='textarea')<textarea name="{{$f}}">{{$v}}</textarea>@elseif($m['type']==='select')<select name="{{$f}}"><option value="">Pilih</option>@foreach($m['options'] as $o)<option value="{{$o}}" @selected($v===$o)>{{$o}}</option>@endforeach</select>@else<input type="{{$m['type']}}" name="{{$f}}" value="{{$v}}" @if($m['type']==='number')step="any"@endif>@endif</label>@endforeach</div><div class="actions"><a href="{{route('resources.index',$module)}}">Batal</a><button class="primary">Simpan</button></div></form>@endsection
BLADE

cat > resources/views/national/login.blade.php <<'BLADE'
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Portal Nasional</title><link rel="stylesheet" href="/assets/app.css"></head><body class="login"><form class="login-card" method="POST" action="{{route('national.login.submit')}}">@csrf<div class="seal">善</div><h1>Portal Nasional</h1><p>Yayasan Dejiaohui Indonesia</p>@if(session('error'))<div class="err">{{session('error')}}</div>@endif<input type="email" name="email" placeholder="Email" required><input type="password" name="password" placeholder="Password" required><button class="primary">Masuk Nasional</button></form></body></html>
BLADE

cat > resources/views/national/dashboard.blade.php <<'BLADE'
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Portal Nasional</title><link rel="stylesheet" href="/assets/app.css"></head><body><main class="national"><div class="actions"><div><h1>Portal Nasional</h1><p>Database masing-masing cabang berdiri sendiri.</p></div><form method="POST" action="{{route('national.logout')}}">@csrf<button>Keluar</button></form></div>@if(session('success'))<div class="ok">{{session('success')}}</div>@endif<div class="panel"><h2>Tambah Cabang</h2><form method="POST" action="{{route('national.branches.store')}}" class="grid">@csrf<input name="code" placeholder="Kode, contoh JKT" required><input name="name" placeholder="Nama cabang" required><input name="city" placeholder="Kota"><input name="province" placeholder="Provinsi"><button class="primary">Buat Database Cabang</button></form></div><div class="panel table"><table><thead><tr><th>Kode</th><th>Nama</th><th>Kota</th><th>Database</th></tr></thead><tbody>@foreach($branches as $b)<tr><td>{{$b->code}}</td><td>{{$b->name}}</td><td>{{$b->city}}</td><td>{{$b->database_name}}</td></tr>@endforeach</tbody></table></div></main></body></html>
BLADE

cat > public/assets/app.css <<'CSS'
:root{--m:#741f2f;--d:#48101c;--g:#c99b44;--i:#fffaf0;--line:#eadfd2}*{box-sizing:border-box}body{margin:0;font-family:Inter,system-ui,sans-serif;background:#f6f1ea;color:#29211e}a{color:var(--m);text-decoration:none}button,input,select,textarea{font:inherit}button{cursor:pointer}.shell{display:grid;grid-template-columns:250px 1fr;min-height:100vh}aside{background:linear-gradient(var(--d),#290b11);color:#fff;padding:22px 15px}aside nav{display:grid;gap:5px;margin-top:20px}aside nav a{color:#fff;padding:10px;border-radius:9px}aside nav a:hover{background:#ffffff1c}.brand{display:flex;gap:12px;align-items:center}.brand>b,.seal{display:grid;place-items:center;background:var(--g);color:var(--d);border-radius:14px;font:30px Georgia;width:52px;height:52px}.brand span,.brand small{display:block}.branch{margin:22px 0;padding:13px;border:1px solid #ffffff2a;border-radius:12px}main header{height:72px;background:#fffaf0e8;display:flex;justify-content:space-between;align-items:center;padding:0 25px;border-bottom:1px solid var(--line)}main header small{display:block;color:#766c67}.content,.national{padding:25px;max-width:1400px;margin:auto}.hero{background:linear-gradient(135deg,var(--m),var(--d));color:#fff;padding:30px;border-radius:22px;display:flex;justify-content:space-between;align-items:center}.hero h1{max-width:800px;font:38px Georgia;margin:8px 0}.hero>b{font:72px Georgia;color:#efd18a}.stats{display:grid;grid-template-columns:repeat(5,1fr);gap:14px;margin:20px 0}.stats article,.panel{background:#fff;border:1px solid var(--line);border-radius:15px;padding:18px;box-shadow:0 7px 22px #3823160b}.stats span,.stats strong{display:block}.stats strong{font-size:23px;color:var(--d);margin-top:6px}.actions{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:15px}.primary{background:var(--m)!important;color:#fff!important;border:0;padding:11px 15px;border-radius:9px}input,select,textarea,button{padding:10px 12px;border:1px solid #d9cec2;border-radius:9px;background:#fff}textarea{min-height:90px}.table{overflow:auto;padding:0}table{border-collapse:collapse;width:100%}th,td{padding:12px;border-bottom:1px solid #eee3d7;text-align:left;white-space:nowrap}th{background:#fbf7f1}.inline{display:inline}.grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}.grid label{display:grid;gap:6px;font-weight:650}.ok,.err{padding:12px;border-radius:9px;margin-bottom:12px}.ok{background:#e7f6ef;color:#17553e}.err{background:#fff0f0;color:#802525}.login{min-height:100vh;display:grid;place-items:center;background:linear-gradient(135deg,var(--d),var(--m));padding:20px}.login-card{width:min(420px,100%);background:var(--i);padding:30px;border-radius:22px;display:grid;gap:13px;text-align:center}.seal{margin:auto;border-radius:50%;width:70px;height:70px;font-size:42px}.national{max-width:1100px}@media(max-width:900px){.shell{grid-template-columns:1fr}aside{position:relative}.stats{grid-template-columns:1fr 1fr}.grid{grid-template-columns:1fr}.hero>b{display:none}}@media(max-width:550px){.stats{grid-template-columns:1fr}.hero h1{font-size:28px}}
CSS

cat > README.md <<'MD'
# SIYADI Dejiaohui Indonesia — Pilot v1

Arsitektur mengikuti pola aplikasi RESTO:

- backend Laravel di hosting;
- satu database pusat;
- database SQLite tersendiri untuk setiap cabang;
- satu APK Android WebView yang mengakses `https://yayasan.ias4u.my.id`.

## Instalasi Domainesia

Ekstrak source ke:

```text
/home/iasumyid/yayasan.ias4u.my.id
```

Document Root:

```text
/home/iasumyid/yayasan.ias4u.my.id/public
```

Jalankan:

```bash
cd /home/iasumyid/yayasan.ias4u.my.id
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate --force
mkdir -p database/tenants
php artisan system:install-demo --force
chmod -R 775 storage bootstrap/cache database
php artisan optimize:clear
```

Pastikan PHP 8.3+, `pdo_sqlite`, dan `sqlite3` aktif.

## Login pilot

Cabang Surabaya:

- URL: `/login`
- Email: `admin.sby@dejiaohui.id`
- Password: `Cabang123!`

Portal nasional:

- URL: `/national/login`
- Email: `admin@dejiaohui.id`
- Password: `Admin123!`

Ganti password demo setelah pengujian.
MD

cp .env.example .env
php artisan key:generate --force
mkdir -p database/tenants
touch database/central.sqlite
php artisan system:install-demo --force
php artisan route:list >/dev/null
rm -f .env database/central.sqlite database/tenants/*.sqlite
rm -rf vendor

cd "$BUILD"
mkdir -p android/app/src/main/java/id/dejiaohui/siyadi

cat > android/settings.gradle <<'GRADLE'
pluginManagement { repositories { google(); mavenCentral(); gradlePluginPortal() } }
dependencyResolutionManagement { repositoriesMode.set(RepositoriesMode.FAIL_ON_PROJECT_REPOS); repositories { google(); mavenCentral() } }
rootProject.name='SIYADI'
include ':app'
GRADLE
cat > android/build.gradle <<'GRADLE'
plugins {
    id 'com.android.application' version '8.5.2' apply false
}
GRADLE
cat > android/app/build.gradle <<'GRADLE'
plugins { id 'com.android.application' }

android {
    namespace 'id.dejiaohui.siyadi'
    compileSdk 35
    defaultConfig {
        applicationId 'id.dejiaohui.siyadi'
        minSdk 23
        targetSdk 35
        versionCode 1
        versionName '1.0.0'
    }
}
GRADLE
cat > android/app/src/main/AndroidManifest.xml <<'XML'
<manifest xmlns:android="http://schemas.android.com/apk/res/android">
    <uses-permission android:name="android.permission.INTERNET" />
    <application android:theme="@style/AppTheme" android:label="Yayasan Dejiaohui" android:usesCleartextTraffic="false">
        <activity android:name=".MainActivity" android:exported="true">
            <intent-filter>
                <action android:name="android.intent.action.MAIN" />
                <category android:name="android.intent.category.LAUNCHER" />
            </intent-filter>
        </activity>
    </application>
</manifest>
XML
mkdir -p android/app/src/main/res/values
cat > android/app/src/main/res/values/styles.xml <<'XML'
<resources><style name="AppTheme" parent="android:style/Theme.Material.Light.NoActionBar"><item name="android:fontFamily">sans</item><item name="android:colorAccent">#C99B44</item><item name="android:statusBarColor">#48101C</item><item name="android:navigationBarColor">#48101C</item></style></resources>
XML
cat > android/app/src/main/java/id/dejiaohui/siyadi/MainActivity.java <<'JAVA'
package id.dejiaohui.siyadi;

import android.app.Activity;
import android.os.Bundle;
import android.webkit.WebChromeClient;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;

public class MainActivity extends Activity {
    private WebView webView;
    @Override public void onCreate(Bundle state) {
        super.onCreate(state);
        webView = new WebView(this);
        setContentView(webView);
        WebSettings s = webView.getSettings();
        s.setJavaScriptEnabled(true);
        s.setDomStorageEnabled(true);
        s.setAllowFileAccess(true);
        s.setBuiltInZoomControls(false);
        webView.setWebViewClient(new WebViewClient());
        webView.setWebChromeClient(new WebChromeClient());
        if (state == null) webView.loadUrl("https://yayasan.ias4u.my.id");
    }
    @Override public void onBackPressed() { if (webView.canGoBack()) webView.goBack(); else super.onBackPressed(); }
}
JAVA

cd "$BUILD/android"
gradle --no-daemon assembleDebug
cd "$BUILD"

mkdir -p package/server package/android-source package/docs
cp -a server/. package/server/
cp -a android/. package/android-source/
cp server/README.md package/README.md
cat > package/docs/PANDUAN-CEPAT.txt <<'TXT'
1. Upload isi folder server ke /home/iasumyid/yayasan.ias4u.my.id
2. Document Root harus /home/iasumyid/yayasan.ias4u.my.id/public
3. Jalankan composer install --no-dev --optimize-autoloader
4. Salin .env.example menjadi .env
5. Jalankan php artisan key:generate --force
6. Jalankan php artisan system:install-demo --force
7. Login cabang: admin.sby@dejiaohui.id / Cabang123!
8. Login nasional: admin@dejiaohui.id / Admin123!
9. APK terhubung ke https://yayasan.ias4u.my.id
TXT

cd package
zip -qr "$RELEASE/SIYADI-Dejiaohui-v1-source.zip" server android-source docs README.md
cd "$ROOT"
cp "$BUILD/android/app/build/outputs/apk/debug/app-debug.apk" "$RELEASE/Yayasan-Dejiaohui-v1-debug.apk"
sha256sum "$RELEASE"/* > "$RELEASE/SHA256SUMS.txt"
cat > "$RELEASE/BUILD_INFO.txt" <<EOF
SIYADI Dejiaohui Indonesia v1.0.0 Pilot
Built: $(date -u +'%Y-%m-%d %H:%M:%S UTC')
Source ZIP: SIYADI-Dejiaohui-v1-source.zip
APK: Yayasan-Dejiaohui-v1-debug.apk
EOF

echo "Release completed"
