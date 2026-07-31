<?php

namespace App\Console\Commands;

use App\Models\Salida;
use App\Models\Tramite;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

#[Signature('insertar_salida')]
#[Description('Comando para insertar los archivos de los servicios pagados')]
class InsertarArchivosSalida extends Command
{

    public function handle()
    {

        try {

            $archivos = Tramite::where('procesado', 0)->get();

            /* if(Cache::get('archivos_salida_s3')){

                $todosLosArchivos = Cache::get('archivos_salida_s3');

            }else{

                $todosLosArchivos = Storage::disk('s3')->files('cobol/salida/');

                Cache::put('archivos_salida_s3', 'value');

            } */

            $todosLosArchivos = Storage::disk('s3')->files('cobol/salida/');

            foreach ($archivos as $archivo) {

                $nombre = str_replace('.TXT', '', $archivo->tramite);

                foreach ($todosLosArchivos as $archivo_s3) {

                    if(str_contains($archivo_s3, $nombre)){

                        $contenido = Storage::disk('s3')->get($archivo_s3);

                        Salida::create([
                            'archivo' => $nombre,
                            'contenido' => $contenido
                        ]);

                        $archivo->update(['procesado' => true]);

                    }

                }

            }

        } catch (\Throwable $th) {

            Log::error('Error al insertar archivos de salida (servicios) ' . $th);

        }

    }

}
