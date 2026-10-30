<?php
    
namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\Facades\DataTables;
    
class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    function __construct()
    {
         $this->middleware('permission:role-list|role-create|role-edit|role-delete', ['only' => ['index','store']]);
         $this->middleware('permission:role-create', ['only' => ['create','store']]);
         $this->middleware('permission:role-edit', ['only' => ['edit','update']]);
         $this->middleware('permission:role-delete', ['only' => ['destroy']]);
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            $roles = Role::with('permissions')->select('roles.*');

            return DataTables::of($roles)
                ->addIndexColumn()
                ->addColumn('permissions', function($row){
                    $count = $row->permissions->count();
                    return '<span class="badge badge-info">' . $count . ' Permissions</span>';
                })
                ->addColumn('action', function($row){
                    $showUrl = route('roles.show', $row->id);
                    $editUrl = route('roles.edit', $row->id);
                    $deleteUrl = route('roles.destroy', $row->id);

                    $btn = '<a href="'.$showUrl.'" class="btn btn-info btn-sm mr-1" title="Show"><i class="fas fa-eye"></i></a>';
                    if(auth()->user()->can('role-edit')){
                        $btn .= '<a href="'.$editUrl.'" class="btn btn-primary btn-sm mr-1" title="Edit"><i class="fas fa-edit"></i></a>';
                    }
                    if(auth()->user()->can('role-delete')){
                        $btn .= '<button type="button" class="btn btn-danger btn-sm btn-delete" data-url="'.$deleteUrl.'" title="Delete"><i class="fas fa-trash"></i></button>';
                    }
                    return $btn;
                })
                ->rawColumns(['permissions', 'action'])
                ->make(true);
        }

        return view('roles.index');
    }
    
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(): View
    {
        $permission = Permission::get();
        return view('roles.create',compact('permission'));
    }
    
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request): RedirectResponse
    {
        $this->validate($request, [
            'name' => 'required|unique:roles,name',
            'permission' => 'required',
        ]);

        try {
            $role = Role::create(['name' => $request->input('name')]);
            $role->syncPermissions($request->input('permission'));

            return redirect()->route('roles.index')
                            ->with('success','Role created successfully');
        } catch (\Throwable $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal membuat role: ' . $e->getMessage());
        }
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id): View
    {
        $role = Role::find($id);
        $rolePermissions = Permission::join("role_has_permissions","role_has_permissions.permission_id","=","permissions.id")
            ->where("role_has_permissions.role_id",$id)
            ->get();
    
        return view('roles.show',compact('role','rolePermissions'));
    }
    
    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id): View
    {
        $role = Role::find($id);
        $permission = Permission::get();
        $rolePermissions = DB::table("role_has_permissions")->where("role_has_permissions.role_id",$id)
            ->pluck('role_has_permissions.permission_id','role_has_permissions.permission_id')
            ->all();
    
        return view('roles.edit',compact('role','permission','rolePermissions'));
    }
    
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id): RedirectResponse
    {
        $this->validate($request, [
            'name' => 'required',
            'permission' => 'required',
        ]);
    
        try {
            $role = Role::find($id);
            $role->name = $request->input('name');
            $role->save();

            $role->syncPermissions($request->input('permission'));

            return redirect()->route('roles.index')
                            ->with('success','Role updated successfully');
        } catch (\Throwable $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal memperbarui role: ' . $e->getMessage());
        }
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        try {
            $role = Role::findOrFail($id);
            if (in_array(strtolower($role->name), ['owner', 'admin'])) {
                throw new \Exception('Role inti (' . $role->name . ') tidak dapat dihapus.');
            }
            if ($role->users()->count() > 0) {
                throw new \Exception('Role tidak dapat dihapus karena masih digunakan oleh user.');
            }

            $role->delete();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Role deleted successfully'
                ]);
            }
            return redirect()->route('roles.index')
                            ->with('success','Role deleted successfully');
        } catch (\Throwable $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 422);
            }
            return redirect()->route('roles.index')
                            ->with('error', $e->getMessage());
        }
    }
}