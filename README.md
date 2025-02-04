## API - Consulta de Crédito
API para consultar disponibilidade de crédito para um determinado CPF e informar qual
é a melhor oportunidade a ser ofertada ao unuário.
## Tecnologias Usadas

- [Laravel](https://laravel.com/)

## Instalação


```sh
git clone https://github.com/1PedroSoares/GoSatTeste.git

composer install

php artisan serve

```
## Como consultar a API:

Realizar request de GET na URL: http://127.0.0.1:8000/ofertasdecredito/{$meucpf}/{$meuvalorsolicitado}
