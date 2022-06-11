<?php

namespace App\Http\Controllers;

use App\Models\Movements;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function add_user(Request $request)
    {
        if (User::where('email', $request->email)->exists()) {
            return response('O e-mail informado já está cadastrado.');
        }
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'birthday' => date("Y-m-d", strtotime($request->birthday))
        ]);
        return response('Usuário cadastrado com sucesso');
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

    public function csv_movements($filter, Request $request)
    {
        if ($filter == '30dias') {
            $lista = DB::table('users')
                ->join('movements', 'users.id', '=', 'movements.user_id')
                ->select('users.name', 'users.email', 'users.birthday', 'movements.movement', 'movements.value', 'movements.created_at')
                ->get();
            $file = $this->generate_csv($lista);
            //dd($file);
            return response($file);
        } else if (is_numeric(substr($request->opcao, 0, 2)) == true && substr($request->opcao, 2, 1) == '/' && is_numeric(substr($request->opcao, 3, 2)) == true) {

            return response($request->opcao);
        } else if ($request->opcao == 'tudo') {

            return response($request->opcao);
        } else {
            return response("Informe algum filtro válido para a geração do arquivo .csv");
        }
    }

    public function remove_accent($string)
    {
        return strtr(utf8_decode($string), utf8_decode('àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ'), 'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY');
    }

    public function generate_csv($lista)
    {
        $cabecalho = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename='relatorio.csv'",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $colunas = array('Nome', 'Email', 'Aniversario', 'Operacao', 'Valor', 'Data da operacao');

        $callback = function () use ($lista, $colunas) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $colunas);

            foreach ($lista as $item) {
                $row['Nome']  = $item->name;
                $row['Email']    = $item->email;
                $row['Aniversario']    = $item->birthday;
                $row['Operacao']  = $item->movement;
                $row['Valor']  = $item->value;
                $row['Data_da_operacao']  = $item->created_at;
                fputcsv($file, array($row['Nome'], $row['Email'], $row['Aniversario'], $row['Operacao'], $row['Valor'], $row['Data_da_operacao']));
            }
            fclose($file);
        };
        dd($lista);
        return response()->stream($callback, 200, $cabecalho);
    }
}
