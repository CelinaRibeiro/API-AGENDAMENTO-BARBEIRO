<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

use App\Models\User;

class AuthController extends Controller
{
    public function __construct() {
        $this->middleware('auth:api', ['except' => ['create', 'login', 'unauthorized']]);
    }

    public function create(Request $request) {
        $array = ['error' => ''];

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if(!$validator->fails()) {
            $name = $request->input('name');
            $email = $request->input('email');
            $password = $request->input('password');

            //antes de criar user preciso ver se o email que ele digitou já está cadastrado
            $emailExists = User::where('email', $email)->count();
            if($emailExists === 0) {

                //gero o hash da senha 
                $hash = password_hash($password, PASSWORD_DEFAULT);

                //cria novo usuário
                $newUser = new User();
                $newUser->name = $name;
                $newUser->email = $email;
                $newUser->password = $hash; //salvo o hash da senha
                $newUser->save();

                //logar com o usuário recem criado
                $token = auth()->attempt([
                    'email' => $email,
                    'password' => $password
                ]);

                if(!$token) {
                    $array['error'] = 'Ocorreu um erro!';
                    return $array;
                }

                $info = auth()->user(); //pega as informações do user para logar
                $info['avatar'] = url('media/avatars/'.$info['avatar']); //monto a url completa
                $array['data'] = $info;
                $array['token'] = $token;
            } else {
                $array['error'] = 'E-mail já cadastrado!';
                return $array;
            }
        } else {
            $array['error'] = 'Dados incorretos';
            return $array;
        }
        return $array;
    }

    public function login(Request $request) {
        $array = ['error' => ''];

        //recebe os dados
        $email = $request->input('email');
        $password = $request->input('password');

        //logar com o usuário 
        $token = auth()->attempt([
            'email' => $email,
            'password' => $password
        ]);

        if(!$token) {
            $array['error'] = 'E-mail e/ou senha incorretos!';
            return $array;
        }

        //pega as informações do user para logar
        $info = auth()->user();
        $info['avatar'] = url('media/avatars/'.$info['avatar']);
        $array['data'] = $info;
        $array['token'] = $token;

        return $array;
    }

    public function logout() {
        auth()->logout();
        return ['error' => ''];
    }

    //refresh para gerar un novo token 
    public function refresh() {
        $array = ['error' => ''];

        $token = auth()->refresh();

        //pass novamente as informações do user 
        $info = auth()->user();
        $info['avatar'] = url('media/avatars/'.$info['avatar']);
        $array['data'] = $info;
        $array['token'] = $token;

        return $array;
    }

    //retorna  não autorizado
    public function unauthorized() {
        return response()->json([
            'error' => 'Não autorizado.'
        ], 401);
    }
}
