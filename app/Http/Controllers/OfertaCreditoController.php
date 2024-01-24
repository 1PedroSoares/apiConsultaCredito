<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;//USO A CLASSE CLIENT DO PACOTE GUZZLEHTTP

class OfertaCreditoController extends Controller
{
   public function analiseCredito($cpf, $valorSolicitado){ 

      $valorSolicitado = (int) $valorSolicitado; //CONVERTE STRING PARA INT PARA FAZER O CÁLCULO DE JUROS ANUAL
      $client = new Client();//CRIO UMA NOVA INSTÂNCIA DA CLASSE CLIENT
      $ofertas = []; //DECLARO UM ARRAY PARA ARMAZENAR TODOS OS OBJETOD DE OFERTAS
      $response = $client->post('https://dev.gosat.org/api/v1/simulacao/credito',[//FAÇO UMA SOLICITAÇÃO HTTP PARA A API DA GOSAT E //ARMAZENO NA VARIÁVEL RESPONSE (QUE É A RESPOSTA DA API)
         'headers' => [ //DEFININDO OS CABEÇALHOS DA SOLICITAÇÃO HTTP
            'Content-Type' => 'application/json', //DEFINO O TIPO DE CONTEÚDO DA SOLICITAÇÃO HTTP COMO DADOS JSON
         ],
         'json' => [
               "cpf" => $cpf //DEFINO O CORPO DA SOLICITAÇÃO COMO UM ARRAY JSON E ADICIONA O CPF AO CORPO
         ]
      ])
      ->getBody()
      ->getContents(); //TRANSFORMO O RESPONSE EM JSON

      $instituicoes = json_decode($response)->instituicoes; //TRANSFORMO O JSON EM OBJETO E PEGO AS INSTITUIÇÕES DO RESPONSE

      for ($i=0; $i < count($instituicoes) ; $i++) { //UM LAÇO DE REPETIÇÃO PARA PEGAR AS INFORMAÇÕES DOS BANCOS
         $idInstituicao = $instituicoes[$i]->id;
         $modalidades = $instituicoes[$i]->modalidades;
         // OUTRO LAÇO DE REPETIÇÃO PARA FAZER A REQUEST PARA PEGAR TODAS AS OFERTAS DE CRÉDITO DAS INSTITUIÇÕES
         for ($j=0; $j < count($modalidades) ; $j++) { 
            $codigoModalidade = $modalidades[$j]->cod;

            $responseOferta = $client->post('https://dev.gosat.org/api/v1/simulacao/oferta',[
               'headers' => [
                  'Content-Type' => 'application/json',
               ],
               'json' => [
                     "cpf" => $cpf, //CONSULTO SE O CPF INFORMADO É VALIDO E PEGAR AS INFORMAÇÕES SOBRE ESSE CPF
                     "instituicao_id" => $idInstituicao,
                     "codModalidade" => $codigoModalidade,
               ]
            ])
            ->getBody()
            ->getContents();
            
            $oferta = json_decode($responseOferta);
            if($oferta->valorMin >= $valorSolicitado){ //DEFINO A LÓGICA DE SÓ PEGAR AS OFERTAS QUE FOR IGUAL OU MAIOR AO VALOR SOLICITADO

               $taxaJurosAnual = $oferta->jurosMes * 12;//CALCULO A TAXA DE JUROS ANUAL
               $taxaComposta = pow(1 + $taxaJurosAnual / 100, $oferta->QntParcelaMax); //CALCULO A TAXA DE JUROS COMPOSTA
               $valorAPagar = (int) ($valorSolicitado * $taxaComposta);

               array_push($ofertas, [//EMPILHO TODAS AS VARIÁVEIS DA API DENTRO DO ARRAY DE OFERTAS
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

      usort($ofertas, function($oferta1, $oferta2){//USO UMA FUNÇÃO USORT PARA ORGANIZAR AS OFERTAS PELA TAXA DE JUROS
         return $oferta1['taxaJuros'] <=> $oferta2['taxaJuros'];
      });
      return $ofertas;

   }
}
