<?php

namespace App\Http\Controllers;

use App\Models\Movements;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use League\Csv\Writer;
use League\Csv\Reader;

class UserController extends Controller
{
    public function add_user(Request $request)
    {
        if (User::where('email', $request->email)->exists()) {
            return response('O e-mail informado já está cadastrado.');
        }
        $age = Carbon::parse(date("Y-m-d", strtotime($request->birthday)))->age;
        if ($age >= 18) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'birthday' => date("Y-m-d", strtotime($request->birthday))
            ]);
            return response('Usuário cadastrado com sucesso');
        } else {
            return response('Apenas maiores de 18 anos podem se cadastrar');
        }
    }

    public function get_users()
    {
        $users = User::orderby('created_at', 'DESC')->get();
        return response()->json($users);
    }

    public function show_user($id)
    {
        $user = User::where('id', $id)->first();
        if (!$user) {
            return response('Usuário não encontrado');
        } else {
            return response()->json($user);
        }
    }

    public function delete_user($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response('Usuário não encontrado');
        } else {
            if (Movements::where('id', $id)->exists()) {
                return response('Não foi possível excluir o usuário pois o mesmo há movimentações em seu nome');
            }
            $user->delete();
            return response('Usuário excluído com sucesso');
        }
    }

    public function new_movement(Request $request)
    {
        $user = User::where('id', $request->user_id)->first();
        if (!$user) {
            return response('Usuário não localizado');
        } else {
            if (
                $this->remove_accent(strtolower($request->movement)) !== "debito" &&
                $this->remove_accent(strtolower($request->movement)) !== "credito" &&
                $this->remove_accent(strtolower($request->movement)) !== "estorno"
            ) {
                return response('A operação informada é inválida, as opções válidas são: débito, crédito eestorno.');
            } else {
                if ($request->value > 0) {
                    $movement = Movements::create([
                        "movement" => $request->movement,
                        "value" => $request->value,
                        "user_id" => $request->user_id
                    ]);
                    return response('Operação adicionada com sucesso!');
                } else {
                    return response('Informe um valor válido para a operação');
                }
            }
        }
    }

    public function get_movements()
    {
        $users_movements = DB::table('users')
            ->join('movements', 'users.id', '=', 'movements.user_id')
            ->select('users.name', 'users.email', 'users.birthday', 'movements.movement', 'movements.value', 'movements.created_at')
            ->paginate(5);
        return response($users_movements);
    }

    public function delete_movement($user_id, $mov_id)
    {
        $user = User::find($user_id);
        if (!$user) {
            return response('Usuário não localizado');
        } else {
            $user_mov = Movements::where('user_id', $user_id)->where('id', $mov_id);
            if ($user_mov->exists()) {
                $user_mov->delete();
                return response('Operação excluída com sucesso');
            } else {
                return response('A movimentação informada não existe');
            }
        }
    }

    public function csv_movements($id, Request $request)
    {
        $user = User::find($id);
        if (!$user) {
            return response('Usuário não localizado');
        } else if ($user) {
            $movs = Movements::where('user_id', $id)->exists();
            if (!$movs) {
                return response('Nenhuma movimentação relacionada ao usuário foi encontrada');
            }
        }
        if ($request->opcao == '30 dias') {
            $lista = DB::table('users')
                ->join('movements', 'users.id', '=', 'movements.user_id')
                ->select('movements.movement', 'movements.value', 'movements.created_at')
                ->where('movements.created_at', '>', now()->subDays(30)->endOfDay())->where('users.id', '=', $id)
                ->get();
            $file = $this->generate_csv($lista, $id);
            return response($file);
        } else if (is_numeric(substr($request->opcao, 0, 2)) == true && substr($request->opcao, 2, 1) == '/' && is_numeric(substr($request->opcao, 3, 2)) == true) {
            $lista = DB::table('users')
                ->join('movements', 'users.id', '=', 'movements.user_id')
                ->select('movements.movement', 'movements.value', 'movements.created_at')
                ->whereBetween('movements.created_at', [substr($request->opcao, 3, 2) . '-' . substr($request->opcao, 0, 2) . '-' . '01', substr($request->opcao, 3, 2) . '-' . substr($request->opcao, 0, 2) . '-' . '31'])
                ->where('users.id', '=', $id)
                ->get();
            $file = $this->generate_csv($lista, $id);
            return response($file);
        } else if ($request->opcao == 'tudo') {
            $lista = DB::table('users')
                ->join('movements', 'users.id', '=', 'movements.user_id')
                ->select('movements.movement', 'movements.value', 'movements.created_at')
                ->where('users.id', '=', $id)
                ->get();
            $file = $this->generate_csv($lista, $id);
            return response($file);
        } else {
            return response("Informe algum filtro válido para a geração do arquivo .csv");
        }
    }

    public function edit_initial_balance($id, Request $request)
    {
        $user = User::find($id);
        if ($user) {
            if (isset($request->value) == true && $request->value > 0) {
                $user_update = User::where('id', $id)->update(['initial_balance' => $request->value]);
                return response('Saldo inicial atualizado');
            } else {
                return response('O valor de saldo informado é inválido');
            }
        } else {
            return response('Usuário não localizado');
        }
    }

    public function sum_movements_initial_balance($id)
    {
        $user = User::find($id);
        if ($user) {
            $movements_sum = Movements::where('user_id', $id)->sum('value');
            return response('Valor de todas as movimentações mais saldo inicial : R$ ' . $movements_sum + $user->initial_balance);
        } else {
            return response('Usuário não localizado');
        }
    }

    public function remove_accent($string)
    {
        return strtr(utf8_decode($string), utf8_decode('àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ'), 'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY');
    }

    public function generate_csv($lista, $user_data = null)
    {
        $user = User::find($user_data);
        $csv = Writer::createFromFileObject(new \SplTempFileObject());
        if($user_data){
            $csv->insertOne(['Nome', 'Email', 'Aniversario','Saldo Inicial']);
            $csv->insertOne([$user->name, $user->email, $user->birthday, $user->initial_balance]);
        }
        $csv->insertOne(['Operacao', 'Valor', 'Data da operacao']);
        foreach ($lista as $item) {
            $csv->insertOne((array)$item);
        }
        $csv->output('relatorio' . Carbon::now() . '.csv');
    }
}
