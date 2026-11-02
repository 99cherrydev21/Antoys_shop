<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
class UserController extends Controller
{
    /**
     * Comprueba que el usuario autenticado sea jefe.
     */
private function authorizeJefe(): void
{
    $roleName = mb_strtolower(
        trim((string) (Auth::user()?->role?->name ?? ''))
    );

    abort_unless(
        $roleName === 'jefe',
        403,
        'No tienes permiso para administrar usuarios.'
    );
}
    /**
     * Mostrar usuarios.
     */
   public function index()
{
    $this->authorizeJefe();

    $users = User::with('role')
        ->orderBy('name')
        ->paginate(10);

    $roles = Role::orderBy('name')->get();

    return view('users.index', compact('users', 'roles'));
}


    /**
     * Guardar un usuario nuevo.
     */
   public function store(Request $request, WhatsAppService $whatsAppService)
{
    $this->authorizeJefe();

    $validated = $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'phone' => [
            'required',
            'string',
            'max:20',
            'regex:/^[0-9+\s()-]+$/',
            'unique:users,phone',
        ],
        'role_id' => [
            'required',
            'integer',
            'exists:roles,id',
        ],
    ], [
        'name.required' => 'El nombre es obligatorio.',
        'phone.required' => 'El número de celular es obligatorio.',
        'phone.unique' => 'Ese número de celular ya está registrado.',
        'phone.regex' => 'El número contiene caracteres no válidos.',
        'role_id.required' => 'Selecciona un rol.',
        'role_id.exists' => 'El rol seleccionado no existe.',
    ]);

    $role = Role::findOrFail($validated['role_id']);

    // Generar una contraseña temporal segura.
    $temporaryPassword = Str::password(14);

    // Crear al usuario. El modelo User aplica el hash de la contraseña.
    $user = DB::transaction(function () use ($validated, $temporaryPassword) {
        return User::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'role_id' => $validated['role_id'],
            'password' => $temporaryPassword,
        ]);
    });

    $loginUrl = url('/login');

    $message = "¡Bienvenido/a a Antoys!\n\n"
        . "Usuario: {$user->phone}\n"
        . "Contraseña:{$temporaryPassword}\n\n"
        . "Ingresa aquí: {$loginUrl}\n\n";


    // Enviar las credenciales al número registrado.
    $result = $whatsAppService->sendMessage(
        $user->phone,
        $message
    );

    if (!($result['success'] ?? false)) {
        // No eliminar al usuario si WhatsApp falla.
        // La contraseña no se muestra en la página.
        return redirect()
            ->route('users.index')
            ->with('warning',
                'El usuario se creó, pero no se pudo enviar el mensaje de WhatsApp. '
                . 'Revisa la conexión y los registros antes de volver a intentarlo.'
            );
    }

    return redirect()
        ->route('users.index')
        ->with('success',
            'Usuario creado correctamente. Las credenciales fueron enviadas a WhatsApp.'
        );
}
    /**
     * Mostrar formulario de edición.
     */
    public function edit(User $user)
    {
        $this->authorizeJefe();

        $roles = Role::orderBy('name')->get();

        return view('users.edit', compact('user', 'roles'));
    }

    /**
     * Actualizar datos de un usuario.
     */
    public function update(Request $request, User $user)
    {
        $this->authorizeJefe();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],

            'phone' => [
                'required',
                'string',
                'max:20',
                Rule::unique('users', 'phone')->ignore($user->id),
            ],

            'role_id' => [
                'required',
                'integer',
                'exists:roles,id',
            ],

            'password' => [
                'nullable',
                'string',
                'min:8',
                'confirmed',
            ],
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'phone.required' => 'El número de celular es obligatorio.',
            'phone.unique' => 'Ese número de celular ya está registrado.',
            'role_id.required' => 'Selecciona un rol.',
            'role_id.exists' => 'El rol seleccionado no existe.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        // Evita que el jefe cambie su propio rol y se quite sus permisos.
    if (
        (int) $user->id === (int) Auth::id()
        && (int) $validated['role_id'] !== (int) $user->role_id
    ) {
        return back()
            ->withInput()
            ->withErrors([
                'role_id' => 'No puedes cambiar tu propio rol.',
            ]);
    }

        $user->name = $validated['name'];
        $user->phone = $validated['phone'];
        $user->role_id = $validated['role_id'];

        // La contraseña solo cambia si se escribió una nueva.
        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()
            ->route('users.index')
            ->with('success', 'Los datos del usuario se actualizaron correctamente.');
    }
    /**
 * Activar o desactivar un usuario.
 */
    Public function toggleStatus(User $user)
{
    $this->authorizeJefe();

    // El jefe no puede desactivar su propia cuenta.
    if ((int) $user->id === (int) Auth::id()) {
        return redirect()
            ->route('users.index')
            ->with('warning', 'No puedes desactivar tu propia cuenta.');
    }

    $user->is_active = !$user->is_active;
    $user->save();

    $message = $user->is_active
        ? 'El usuario ha sido activado correctamente.'
        : 'El usuario ha sido desactivado correctamente.';

    return redirect()
        ->route('users.index')
        ->with('success', $message);
}

}
