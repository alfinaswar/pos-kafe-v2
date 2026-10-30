<?php
    
namespace App\Http\Controllers;
    
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Outlet;
use Spatie\Permission\Models\Role;
use DB;
use Hash;
use Illuminate\Support\Arr;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\Facades\DataTables;
    
class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request): View|\Illuminate\Http\JsonResponse
    {
        if ($request->ajax()) {
            $users = User::with('roles')->select('users.*');

            return DataTables::of($users)
                ->addIndexColumn()
                ->addColumn('roles', function($row){
                    $badges = '';
                    foreach($row->roles as $role){
                        $badges .= '<span class="badge badge-success mr-1">' . e($role->name) . '</span>';
                    }
                    return $badges ?: '<span class="badge badge-secondary">No Role</span>';
                })
                ->addColumn('action', function($row){
                    $showUrl = route('users.show', $row->id);
                    $editUrl = route('users.edit', $row->id);
                    $deleteUrl = route('users.destroy', $row->id);
                    $csrf = csrf_token();
                    return '
                        <a href="'.$showUrl.'" class="btn btn-info btn-sm mr-1" title="Show"><i class="fas fa-eye"></i></a>
                        <a href="'.$editUrl.'" class="btn btn-primary btn-sm mr-1" title="Edit"><i class="fas fa-edit"></i></a>
                        <button type="button" class="btn btn-danger btn-sm btn-delete" data-url="'.$deleteUrl.'" title="Delete"><i class="fas fa-trash"></i></button>
                    ';
                })
                ->rawColumns(['roles', 'action'])
                ->make(true);
        }

        return view('users.index');
    }
    
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(): View
    {
        $roles = Role::pluck('name','name')->all();
        $outlets = Outlet::all();
        return view('users.create',compact('roles', 'outlets'));
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
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'username' => 'nullable|string|unique:users,username',
            'outlet_id' => 'nullable|exists:outlets,id',
            'password' => 'required|same:confirm-password',
            'roles' => 'required'
        ]);
    
        try {
            $input = $request->all();
            $input['password'] = Hash::make($input['password']);

            $user = User::create($input);
            $user->assignRole($request->input('roles'));

            return redirect()->route('users.index')
                            ->with('success','User created successfully');
        } catch (\Throwable $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal menambahkan user: ' . $e->getMessage());
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
        $user = User::with('outlet')->find($id);
        return view('users.show',compact('user'));
    }
    
    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id): View
    {
        $user = User::find($id);
        $roles = Role::pluck('name','name')->all();
        $userRole = $user->roles->pluck('name','name')->all();
        $outlets = Outlet::all();
    
        return view('users.edit',compact('user','roles','userRole', 'outlets'));
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
            'email' => 'required|email|unique:users,email,'.$id,
            'username' => 'nullable|string|unique:users,username,'.$id,
            'outlet_id' => 'nullable|exists:outlets,id',
            'password' => 'same:confirm-password',
            'roles' => 'required'
        ]);
    
        try {
            $input = $request->all();
            if(!empty($input['password'])){ 
                $input['password'] = Hash::make($input['password']);
            }else{
                $input = Arr::except($input,array('password'));    
            }

            $user = User::find($id);
            $user->update($input);
            DB::table('model_has_roles')->where('model_id',$id)->delete();

            $user->assignRole($request->input('roles'));

            return redirect()->route('users.index')
                            ->with('success','User updated successfully');
        } catch (\Throwable $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal memperbarui user: ' . $e->getMessage());
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
            $user = User::findOrFail($id);

            if (auth()->id() == $id) {
                throw new \Exception('Anda tidak dapat menghapus akun Anda sendiri.');
            }

            if ($user->shifts()->count() > 0 || $user->sales()->count() > 0) {
                throw new \Exception('User tidak dapat dihapus karena memiliki riwayat shift atau transaksi penjualan.');
            }

            $user->delete();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'User deleted successfully'
                ]);
            }

            return redirect()->route('users.index')
                            ->with('success','User deleted successfully');
        } catch (\Throwable $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 422);
            }

            return redirect()->route('users.index')
                            ->with('error', $e->getMessage());
        }
    }
}