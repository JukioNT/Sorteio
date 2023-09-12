<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\GrupoSorteio;
use App\Models\Participante;
use App\Models\AmigoSecreto;

class GrupoSorteioController extends Controller
{
    public function __construct(){
        $this->middleware('auth');
    }

    public function index()
    {
        $dados = GrupoSorteio::with('User')->get();
        foreach($dados as $item){
            $item->totalParticipantes = Participante::where('grupoSorteio_id', '=', $item->id)->count();
            $sorteio = AmigoSecreto::where('grupoSorteio_id', '=', $item->id)->first();
            if(isset($sorteio))
                $item->sorteioRealizado = 1;
            else 
                $item->sorteioRealizado = 0;
            $participante = AmigoSecreto::where('participante_id', '=', Auth::id())->where('grupoSorteio_id', '=', $item->id)->first();
            if(isset($participante))
                $item->souParticipante = 1;
            else{
                $item->souParticipante = 0;
            }
        }
        return view('sorteios', compact('dados'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('novoSorteio');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $dados = new GrupoSorteio();
        $dados->user_id = Auth::id();
        $dados->dataSorteio = $request->input('dataSorteio');
        $dados->vrMinimo = $request->input('vrMinimo');
        $dados->vrMaximo = $request->input('vrMaximo');
        $dados->save();
        return redirect()->action(
            [ParticipanteController::class, 'create'], ['id' => $dados->id]
        );

        
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $dados = GrupoSorteio::find($id);
        if(isset($dados))
            return view('editarSorteio', compact('dados'));
        return redirect('/grupoSorteio');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $dados = GrupoSorteio::find($id);
        if(isset($dados)){
            $dados->user_id = Auth::id();
            $dados->dataSorteio = $request->input('dataSorteio');
            $dados->vrMinimo = $request->input('vrMinimo');
            $dados->vrMaximo = $request->input('vrMaximo');
            $dados->save();
        }
        return redirect('/grupoSorteio');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $dados = GrupoSorteio::find($id);
        if(isset($dados))
            $dados->delete();
        return redirect('/grupoSorteio');   
    }
    
    public function sortear($id){
        $dados = Participante::where('grupoSorteio_id', '=', $id)->with('User')->get();
        $quantidade = Participante::where('grupoSorteio_id', '=', $id)->count();
        
        $participantes = array();

        foreach($dados as $participante){
            $participantes[] = $participante->id;
        }

        $participantesEmbaralhado = $participantes;
        shuffle($participantesEmbaralhado);

        $iguais = GrupoSorteioController::VerificarIguais($participantes, $participantesEmbaralhado, $quantidade);

       if(!empty($iguais)){
            while(!empty($iguais)){
                foreach($iguais as $posicao){
                    $trocar = rand(0, $quantidade-1); //posicao que vai trocar
    
                    $id = $dados[$posicao]->id; //valor do igual
    
                    $dados[$posicao]->id = $dados[$trocar]->id;
                    $dados[$trocar]->id = $id;
                }
                $participantes = array();
                foreach($dados as $participante){
                    $participantes[] = $participante->id;
                }
                $iguais = GrupoSorteioController::VerificarIguais($participantes, $participantesEmbaralhado, $quantidade);
            }
            for($i = 0; $i < $quantidade; $i++){
                $r = new AmigoSecreto();
                $r->participante_id = $participantes[$i];
                $r->participanteSorteado_id = $participantesEmbaralhado[$i];
                $r->grupoSorteio_id = $dados[$i]->grupoSorteio_id;
                $r->save();
            }
            return redirect('/grupoSorteio')->with('success', 'Sorteio realizado com sucesso!!');
       }else{ 
            for($i = 0; $i < $quantidade; $i++){
                $r = new AmigoSecreto();
                $r->participante_id = $participantes[$i];
                $r->participanteSorteado_id = $participantesEmbaralhado[$i];
                $r->grupoSorteio_id = $dados[$i]->grupoSorteio_id;
                $r->save();
            }
            return redirect('/grupoSorteio')->with('success', 'Sorteio realizado com sucesso!!');
       }
    }

    public function deletarSorteio($id){
        $dados = AmigoSecreto::where('grupoSorteio_id', '=', $id)->get();
        foreach($dados as $item)
            $item->delete();
        return redirect('/grupoSorteio');
    }

    public function VerificarIguais($participantes, $participantesEmbaralhado, $quantidade){
        $iguais = array();
        for($i = 0; $i < $quantidade; $i++){
            if($participantes[$i] == $participantesEmbaralhado[$i]){
                $iguais[] = $i; 
            }
        }
        return $iguais;
    }
}
