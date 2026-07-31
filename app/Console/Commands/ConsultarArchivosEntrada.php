<?php

namespace App\Console\Commands;

use App\Models\Tramite;
use Exception;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

#[Signature('consultar_entrada')]
#[Description('Comando para obtener los archivos de entrada (servicios)')]
class ConsultarArchivosEntrada extends Command
{


    public function handle()
    {

        $url = 'http://10.0.32.14/finanzas/solicitud/';

        try {

            $response =  Http::get($url);

            if($response->status() !== 200){

                throw new Exception("Error de comunicación con la url: " . $url);

            }

            $html = $response->body();

            preg_match_all('/href="([^"]+)"/i', $html, $matches);

            $archivos = collect($matches[1])
                        ->filter(function ($archivo) {
                            return str_contains($archivo, now()->format('Ymd'));
                        })
                        ->values();

            foreach($archivos as $archivo){

                $entrada = Tramite::where('tramite', $archivo)->first();

                if(! $entrada){

                    $contenido = Http::get($url.$archivo)->body();

                    Storage::disk('s3')->put('cobol/entrada/'. $archivo, $contenido);

                    Tramite::create(['tramite' => $archivo]);

                    Cache::forget('archivos_salida_s3');

                }

            }

        } catch (Exception $ex) {

            Log::error('Error al consultar archivos de entrada (servicios) ' . $ex->getMessage());

        } catch (\Throwable $th) {

            Log::error('Error al consultar archivos de entrada (servicios) ' . $th);

        }

    }
}
