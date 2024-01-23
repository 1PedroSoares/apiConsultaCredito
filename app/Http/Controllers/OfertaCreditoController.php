<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;

class OfertaCreditoController extends Controller
{
   public function analiseCredito($cpf, $valorSolicitado){

      $valorSolicitado = (int) $valorSolicitado;
      $client = new Client();
      $ofertas = [];
      $response = $client->post('https://dev.gosat.org/api/v1/simulacao/credito',[
         'headers' => [
            'Content-Type' => 'application/json',
         ],
         'json' => [
               "cpf" => $cpf
         ]
      ])
      ->getBody()
      ->getContents();

      $instituicoes = json_decode($response)->instituicoes;

      for ($i=0; $i < count($instituicoes) ; $i++) { 
         $idInstituicao = $instituicoes[$i]->id;
         $modalidades = $instituicoes[$i]->modalidades;

         for ($j=0; $j < count($modalidades) ; $j++) {
            $codigoModalidade = $modalidades[$j]->cod;

            $responseOferta = $client->post('https://dev.gosat.org/api/v1/simulacao/oferta',[
               'headers' => [
                  'Content-Type' => 'application/json',
               ],
               'json' => [
                     "cpf" => $cpf,
                     "instituicao_id" => $idInstituicao,
                     "codModalidade" => $codigoModalidade,
               ]
            ])
            ->getBody()
            ->getContents();

            $oferta = json_decode($responseOferta);
            if($oferta->valorMin >= $valorSolicitado){

               $taxaJurosAnual = $oferta->jurosMes * 12;
               $taxaComposta = pow(1 + $taxaJurosAnual / 100, $oferta->QntParcelaMax);
               $valorAPagar = (int) ($valorSolicitado * $taxaComposta);

               array_push($ofertas, [
                  'instituicaoFinanceira' => $instituicoes[$i]->nome,
                  'modalidadeCredito' => $modalidades[$j]->nome,
                  'valorAPagar' => $valorAPagar,
                  'valorSolicitado' => $valorSolicitado,
                  'taxaJuros' => $oferta->jurosMes,
                  'qntParcelas' => $oferta->QntParcelaMax,
               ]);
            }
         }

      }

      usort($ofertas, function($oferta1, $oferta2){
         return $oferta1['taxaJuros'] <=> $oferta2['taxaJuros'];
      });
      return $ofertas;

   }
}
