<?php
namespace Phpanonymous\It\Controllers\Baboon;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Phpanonymous\It\Controllers\Baboon\BaboonDataTable;
use Phpanonymous\It\Controllers\Baboon\BaboonShowPage;
use Phpanonymous\It\Controllers\Baboon\CurrentModuleMaker\BaboonDeleteModule;
use Phpanonymous\It\Controllers\Baboon\CurrentModuleMaker\BaboonModule;
use Phpanonymous\It\Controllers\Baboon\MasterBaboon as Baboon;
use Phpanonymous\It\Controllers\Baboon\Statistics;

class Home extends Controller {
	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */

	public function index() {

		if (!empty(request('delete_module'))) {
			// Delete .baboon Text CRUD by request('delete_module')
			return (new BaboonDeleteModule)->init();
		}

		$data = [];

		$baboonModule = (new \Phpanonymous\It\Controllers\Baboon\CurrentModuleMaker\BaboonModule);
		// Load all Modules
		$getAllModule = $baboonModule->getAllModules();

		$data['getAllModule'] = $getAllModule;
		if (!empty(request('module')) && !is_null(request('module'))) {
			$Modulefile = 'baboon/' . request('module');
			// Edit Modules
			$readmodule = $baboonModule->read($Modulefile);
			if ($readmodule === false) {
				// redirect if fails load Files
				header('Location: ' . url('it/baboon-sd'));
				exit;
			} else {
				$data['module_data'] = $readmodule;
				$data['module_last_modified'] = date('Y-m-d h:i:s A T', $baboonModule->lastModified($Modulefile));
			}
		} else {
			$data['module_data'] = null;
			$data['module_last_modified'] = null;
		}

		app()->singleton('module_data', function () use ($data) {
			return $data['module_data'];
		});

		$data['title'] = it_trans('it.baboon-sd');
		//return $data;
		return view('baboon.home', $data);
	}

	public static function autoconvSchemaTableName($conv) {
		if (!in_array(substr($conv, -1), ['s'])) {
			if (substr($conv, -1) == 'y') {
				$conv = substr($conv, 0, -1) . 'ies';
			} else {
				$conv = $conv . 's';
			}
		}
		return $conv;
	}

	public function index_post(Request $r) {

		$this->validate(request(), [
			'project_title' => 'required',
			'controller_name' => 'required',
			'controller_namespace' => 'required',
			'model_name' => 'required',
			'model_namespace' => 'required',
			'lang_file' => 'required',
			'col_name' => 'required',
			'col_type' => 'required',
			'col_name_convention' => 'required',
		], [], [
			'model_name' => it_trans('it.model_name'),
			'project_title' => it_trans('it.project_title'),
			'controller_name' => it_trans('it.controller_name'),
			'lang_file' => it_trans('it.lang_file'),
			'col_name' => it_trans('it.col_name'),
			'col_type' => it_trans('it.col_type'),
			'col_name_convention' => it_trans('it.col_name_convention'),
			'controller_namespace' => it_trans('it.controller_namespace'),
			'model_namespace' => it_trans('it.model_namespace'),
			'model_name' => it_trans('it.model_name'),
		]);

		// Create .baboon Text CRUD
		$prepare_module = new BaboonModule();
		$prepare_module->init();
		$module = $prepare_module->getmodule_data();

		$controller = Baboon::makeController($r, $r->input('controller_namespace'),
			$r->input('model_namespace') . '\\' . $r->input('model_name'),
			$r->input('controller_name'));

		$controllerApi = Baboon::makeControllerApi($r, $r->input('controller_namespace'),
			$r->input('model_namespace') . '\\' . $r->input('model_name'),
			$r->input('controller_name'));

		$model = Baboon::makeModel($r->input('model_namespace'), $r->input('model_name'));
		$migrate = Baboon::migrate($r);

		$model_path = Baboon::check_path($r->input('model_namespace')); // Make NameSpace Folder Models
		$controller_path = Baboon::check_path($r->input('controller_namespace')); // Make Namespace folder Controller
		$database_path = Baboon::check_path('database\\migrations'); // Make database folder
		$view = Baboon::inputsCreate($r);
		$view_update = Baboon::inputsUpdate($r);
		$view_index = Baboon::IndexBlade($r);

		$show_page = BaboonShowPage::show($r);

		$action = Baboon::actions($r);

		Baboon::check_path('app\\Http\\Controllers\\Validations'); // Make Validations folder
		Baboon::check_path('app\\DataTables'); // Make DataTables folder
		//Baboon::check_path('resources\\lang');// Make lang folder
		//Baboon::check_path('resources\\lang\\ar');// Make lang folder
		//Baboon::check_path('resources\\views');// Make views folder
		Baboon::check_path($r->input('admin_folder_path')); // Make views folder
		Baboon::check_path('resources\\assets'); // Make assets folder
		Baboon::check_path('app\\Http\\Controllers\\Api'); // Make assets folder

		if ($controller_path and $model_path and $database_path) {

			if (request()->has('make_controller')) {
				Baboon::write($controller, $r->input('controller_name'), $r->input('controller_namespace'));
			}

			// if (request()->has('make_controller_api')) {
			Baboon::write($controllerApi, $r->input('controller_name') . 'Api', 'App\Http\Controllers/Api');
			// }

			if (request()->has('make_model')) {
				Baboon::write($model, $r->input('model_name'), $r->input('model_namespace'));
			}

			if (request()->has('make_views')) {
				$blade_name = str_replace('controller', '', strtolower($r->input('controller_name')));
				if (!empty($view)) {
					Baboon::check_path($r->input('admin_folder_path') . '\\' . $blade_name);
					Baboon::write($view, 'create.blade', $r->input('admin_folder_path') . '\\' . $blade_name);
				}

				if (!empty($show_page)) {
					Baboon::check_path($r->input('admin_folder_path') . '\\' . $blade_name);
					Baboon::write($show_page, 'show.blade', $r->input('admin_folder_path') . '\\' . $blade_name);
				}

				if (!empty($view_update)) {
					Baboon::check_path($r->input('admin_folder_path') . '\\' . $blade_name);
					Baboon::write($view_update, 'edit.blade', $r->input('admin_folder_path') . '\\' . $blade_name);
				}

				Baboon::check_path($r->input('admin_folder_path') . '\\' . $blade_name . '\\buttons');
				Baboon::write($view_index, 'index.blade', $r->input('admin_folder_path') . '\\' . $blade_name); // Make Index Blade File

				Baboon::write($action, 'actions.blade', $r->input('admin_folder_path') . '\\' . $blade_name . '\\buttons'); // Make action buttons Blade
			}

			$folder2 = str_replace('Controller', '', $r->input('controller_name'));
			if (request()->has('make_datatable')) {
				Baboon::write(BaboonDataTable::dbclass($r), $folder2 . 'DataTable', 'app\\DataTables\\');
			}

			Baboon::write(BaboonValidations::validationClass($r), $folder2 . 'Request', 'app\\Http\\Controllers\\Validations\\');
			// Admin Route List Roles Start//
			$routes = Baboon::RouteListRoles($r);
			Baboon::write($routes, 'AdminRouteList', 'app\\Http\\');
			// Admin Route List Roles End//
			////////////////// Language Files ////////////////////
			$lang_ar = Baboon::Makelang($r);
			Baboon::write($lang_ar, $r->input('lang_file'), 'resources\\lang\\ar\\');

			if (is_dir(base_path('resources/lang/en'))) {
				$lang_en = Baboon::Makelang($r, 'en');
				Baboon::write($lang_en, $r->input('lang_file'), 'resources\\lang\\en\\');
			}

			if (is_dir(base_path('resources/lang/fr'))) {
				$lang_fr = Baboon::Makelang($r, 'fr');
				Baboon::write($lang_fr, $r->input('lang_file'), 'resources\\lang\\fr\\');
			}
			////////////////// Language Files ////////////////////
			if (request()->has('make_migration')) {

				Baboon::write($migrate, $module->migration_file_name, 'database\\migrations');

				// Disable ForignKey Checks DB
				if (request()->has('auto_migrate')) {
					\DB::statement('SET FOREIGN_KEY_CHECKS = 0');

					\DB::table('migrations')
						->where('migration', $module->migration_file_name)
						->delete();

					\Schema::dropIfExists($module->convention_name);

					\Artisan::call('migrate', []);

					if (\DB::table('migrations')->where('migration', $module->migration_file_name)->count() == 0) {
						\DB::table('migrations')->create([
							'migration' => $module->migration_file_name,
							'batch' => @\DB::table('migrations')->orderBy('id', 'desc')->first() + 1,
						]);
					}
					// Enable ForignKey Checks DB
					\DB::statement('SET FOREIGN_KEY_CHECKS = 1');
				}

			}
		}

		//    session()->flash('success', trans('admin.files_created'));
		\Config::set('filesystems.default', 'it');

		//********* Preparing Route Admin ***********/
		$link = strtolower(preg_replace('/Controller|controller/i', '', $r->input('controller_name')));
		$end_route = '////////AdminRoutes/*End*///////////////';
		$namespace_single = explode('App\Http\Controllers\\', $r->input('controller_namespace'))[1];
		$route1 = 'Route::resource(\'' . $link . '\',\'' . $namespace_single . '\\' . $r->input('controller_name') . '\'); ' . "\r\n";
		$route2 = '		Route::post(\'' . $link . '/multi_delete\',\'' . $namespace_single . '\\' . $r->input('controller_name') . '@multi_delete\'); ' . "\r\n";

		$admin_routes = file_get_contents(base_path('routes/admin.php'));

		if (!preg_match("/" . $link . "/i", $admin_routes)) {
			$admin_routes = str_replace($end_route, $route1 . $route2 . "		" . $end_route, $admin_routes);
			\Storage::put('routes/admin.php', $admin_routes);
		}

		// Linked With Ajax Route Start//
		$route3 = '';
		$xi = 0;
		foreach (request('col_name_convention') as $input_ajax) {
			if (!empty(request('link_ajax' . $xi)) && request('link_ajax' . $xi) == 'yes') {
				$explode_name_ajax = explode('|', $input_ajax);
				$col_name_ajax = count($explode_name_ajax) > 0 ? $explode_name_ajax[0] : $input_ajax;
				$route3 = 'Route::post(\'' . $link . '/get/' . str_replace('_', '/', $col_name_ajax) . '\',\'' . $namespace_single . '\\' . request('controller_name') . '@get_' . $col_name_ajax . '\'); ' . "\r\n";

				if (!preg_match("/" . request('controller_name') . "@get_" . $col_name_ajax . "/i", $admin_routes)) {
					$admin_routes = str_replace($end_route, $route3 . "		" . $end_route, $admin_routes);
					\Storage::put('routes/admin.php', $admin_routes);
				}

			}
			$xi++;
		}
		// Linked With Ajax Route End//

		//********* Preparing Route ***********/

		//********* Preparing Route Api ***********/
		$linkapi = strtolower(preg_replace('/Controller|controller/i', '', $r->input('controller_name'))) . 'Api';
		$end_routeapi = '//////// Api Routes /* End */ //////////////';
		$namespace_singleapi = 'Api';
		$route1 = 'Route::resource(\'' . $linkapi . '\',\'' . $namespace_singleapi . '\\' . $r->input('controller_name') . '\'); ' . "\r\n";
		$routeapi = 'Route::post(\'' . $linkapi . '/multi_delete\',\'' . $namespace_singleapi . '\\' . $r->input('controller_name') . '@multi_delete\'); ' . "\r\n";

		$api_routes = file_get_contents(base_path('routes/api.php'));
		if (!preg_match("/" . $link . "/i", $api_routes)) {
			$api_routes = str_replace($end_routeapi, $route1 . $routeapi . "		" . $end_routeapi, $api_routes);
			\Storage::put('routes/api.php', $api_routes);
		}
		//********* Preparing Route End Api ***********/

		//********* Preparing Menu List ***********/
		$admin_menu = file_get_contents(base_path('resources/views/admin/layouts/menu.blade.php'));
		$fa_icon = !empty($r->input('fa_icon')) ? $r->input('fa_icon') : 'fa fa-icons';
		if (!preg_match("/" . $link . "/i", $admin_menu)) {
			$link2 = '{{active_link(\'' . $link . '\',\'menu-open\')}} ';
			$link3 = '{{active_link(\'\',\'active\')}}';
			$link4 = '{{active_link(\'' . $link . '\',\'active\')}}';
			$link5 = '{{trans(\'' . $r->input('lang_file') . '.' . $link . '\')}} ';
			$urlurl = '{{aurl(\'' . $link . '\')}}';
			$title = '{{trans(\'' . $r->input('lang_file') . '.' . $link . '\')}} ';
			$create = '{{trans(\'' . $r->input('lang_file') . '.create\')}} ';

			$newmenu = '@if(admin()->user()->role("' . $link . '_show"))' . "\r\n";
			$newmenu .= '<li class="nav-item ' . $link2 . '">' . "\r\n";
			$newmenu .= '  <a href="#" class="nav-link ' . $link4 . '">' . "\r\n";
			$newmenu .= '    <i class="nav-icon ' . $fa_icon . '"></i>' . "\r\n";
			$newmenu .= '    <p>' . "\r\n";
			$newmenu .= '      ' . $title . '' . "\r\n";
			$newmenu .= '      <i class="right fas fa-angle-left"></i>' . "\r\n";
			$newmenu .= '    </p>' . "\r\n";
			$newmenu .= '  </a>' . "\r\n";
			$newmenu .= '  <ul class="nav nav-treeview">' . "\r\n";
			$newmenu .= '    <li class="nav-item">' . "\r\n";
			$newmenu .= '      <a href="' . $urlurl . '" class="nav-link  ' . $link4 . '">' . "\r\n";
			$newmenu .= '        <i class="' . $fa_icon . ' nav-icon"></i>' . "\r\n";
			$newmenu .= '        <p>' . $title . '</p>' . "\r\n";
			$newmenu .= '      </a>' . "\r\n";
			$newmenu .= '    </li>' . "\r\n";
			$newmenu .= '    <li class="nav-item">' . "\r\n";
			$newmenu .= '      <a href="{{ aurl(\'' . $link . '/create\') }}" class="nav-link">' . "\r\n";
			$newmenu .= '        <i class="fas fa-plus nav-icon"></i>' . "\r\n";
			$newmenu .= '        <p>' . $create . '</p>' . "\r\n";
			$newmenu .= '      </a>' . "\r\n";
			$newmenu .= '    </li>' . "\r\n";
			$newmenu .= '  </ul>' . "\r\n";
			$newmenu .= '</li>' . "\r\n";
			$newmenu .= '@endif' . "\r\n";
			\Storage::put('resources/views/admin/layouts/menu.blade.php', $admin_menu . "\r\n" . $newmenu);
		}

		//********* Preparing Menu List ***********/
		//session()->flash('code', $data_code);
		if (!empty(request('collect'))) {
			(new Statistics)->init();
		}

		return response(['status' => true, 'message' => 'Module - CRUD Generated'], 200);
	}

	public function makeNamespace($type) {
		if (request()->has('namespace')) {
			if ($type == 'controller') {
				\Storage::disk('it')
					->makeDirectory('app/Http/Controllers/' . str_replace('\\', '/', request('namespace')));
			}
		}
	}

}
