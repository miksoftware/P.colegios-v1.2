<?php

namespace Database\Seeders;

use App\Models\ExpenseCode;
use Illuminate\Database\Seeder;

class ExpenseCodeSeeder extends Seeder
{
    public function run(): void
    {
        $codes = [
            ['code' => '2.1.2.01.01.003.01.06', 'name' => 'OTRAS MÁQUINAS PARA USOS GENERALES Y SUS PARTES Y PIEZAS'],
            ['code' => '2.1.2.01.01.003.02.08', 'name' => 'OTRA MAQUINARIA PARA USOS ESPECIALES Y SUS PARTES Y PIEZAS'],
            ['code' => '2.1.2.01.01.003.03.01', 'name' => 'MAQUINAS PARA OFICINA Y CONTABILIDAD Y SUS PARTES Y ACCESORIOS'],
            ['code' => '2.1.2.01.01.003.03.02', 'name' => 'MAQUINARIA DE INFORMÁTICA Y SUS PARTES, PIEZAS Y ACCESORIOS'],
            ['code' => '2.1.2.01.01.003.05.02', 'name' => 'APARATOS TRANSMISORES DE TELEVISION Y RADIO: TELEVISION, VIDEO Y CAMARAS DIGITALES; TELEFONO'],
            ['code' => '2.1.2.01.01.003.05.03', 'name' => 'RADIORRECEPTORES Y RECEPTORES DE TELEVISIÓN; APARATOS PARA LA GRABACIÓN Y REPRODUCCIÓN DE SONIDO Y VIDEO; MICRÓFONOS, ALTAVOCES, AMPLIFICADORES, ETC.'],
            ['code' => '2.1.2.01.01.003.06.02', 'name' => 'INSTRUMENTOS Y APARATOS DE MEDICIÓN, VERIFICACIÓN, ANÁLISIS, DE NAVEGACIÓN Y PARA OTROS FINES (EXCEPTO INSTRUMENTOS ÓPTICOS); INSTRUMENTOS DE CONTROL DE PROCESOS INDUSTRIALES, SUS PARTES, PIEZAS Y ACCESORIOS'],
            ['code' => '2.1.2.01.01.004.01.01.02', 'name' => 'MUEBLES DE TIPO UTILIZADOS EN LA OFICINA'],
            ['code' => '2.1.2.01.01.004.01.01.04', 'name' => 'OTROS MUEBLES N.C.P.'],
            ['code' => '2.1.2.01.01.004.01.02', 'name' => 'INSTRUMENTOS MUSICALES'],
            ['code' => '2.1.2.01.01.004.01.03', 'name' => 'ARTÍCULOS DE DEPORTE'],
            ['code' => '2.1.2.01.01.005.01.01.01', 'name' => 'ANIMALES DE CRIA'],
            ['code' => '2.1.2.01.01.005.01.01.02', 'name' => 'GANADO LECHERO'],
            ['code' => '2.1.2.01.01.005.01.01.08', 'name' => 'OTROS ANIMALES QUE GENERAN PRODUCTOS EN FORMA REPETIDA'],
            ['code' => '2.1.2.01.01.005.01.02.06', 'name' => 'OTROS ÁRBOLES, CULTIVOS Y PLANTAS QUE GENERAN PRODUCTOS EN FORMA REPETIDA'],
            ['code' => '2.1.2.01.01.005.02.03.01.01', 'name' => 'PAQUETES DE SOFTWARE'],
            ['code' => '2.1.2.02.01.000', 'name' => 'AGRICULTURA, SILVICULTURA Y PRODUCTOS DE LA PESCA'],
            ['code' => '2.1.2.02.01.003.01', 'name' => 'DOTACION INSTITUCIONAL DE MATERIAL Y MEDIOS PEDAGOGICOS PARA EL APRENDIZAJE (TEXTOS, LIBROS, MATERIAL DIDACTICO, GUIAS)'],
            ['code' => '2.1.2.02.01.003.02', 'name' => 'IMPRESOS Y PUBLICACIONES'],
            ['code' => '2.1.2.02.01.003.03', 'name' => 'PAPELERÍA Y ÚTILES DE ESCRITORIO'],
            ['code' => '2.1.2.02.01.003.04', 'name' => 'OTROS ARTÍCULOS MANUFACTURADOS N.C.P.'],
            ['code' => '2.1.2.02.01.003.05', 'name' => 'ELEMENTOS DE ASEO Y CAFETERÍA Y OTROS MATERIALES'],
            ['code' => '2.1.2.02.02.005.01', 'name' => 'MANTENIMIENTO DE INFRAESTRUCTURA EDUCATIVA'],
            ['code' => '2.1.2.02.02.005.02', 'name' => 'SERVICIOS DE INSTALACIONES'],
            ['code' => '2.1.2.02.02.005.03', 'name' => 'SERVICIOS DE TERMINACION Y ACABADO DE EDIFICIOS'],
            ['code' => '2.1.2.02.02.006.01', 'name' => 'OTROS SERVICIOS DE ALOJAMIENTO'],
            ['code' => '2.1.2.02.02.006.02', 'name' => 'SERVICIOS DE SUMINISTRO DE COMIDAS'],
            ['code' => '2.1.2.02.02.006.03', 'name' => 'SERVICIO DE TRANSPORTE LOCAL Y TURISTICO DE PASAJEROS'],
            ['code' => '2.1.2.02.02.006.04', 'name' => 'SERVICIOS DE TRANSPORTE DE PASAJEROS, DIFERENTE DEL TRANSPORTE LOCAL Y TURÍSTICO DE PASAJEROS'],
            ['code' => '2.1.2.02.02.006.05', 'name' => 'SERVICIOS POSTALES Y DE MENSAJERÍA'],
            ['code' => '2.1.2.02.02.006.06', 'name' => 'SERVICIOS DE DISTRIBUCIÓN DE ELECTRICIDAD, Y SERVICIOS DE DISTRIBUCIÓN DE GAS'],
            ['code' => '2.1.2.02.02.006.07', 'name' => 'SERVICIOS DE DISTRIBUCIÓN DE AGUA (POR CUENTA PROPIA)'],
            ['code' => '2.1.2.02.02.007.01', 'name' => 'OTROS SERVICIOS FINANCIEROS NCP (EXCEPTO LOS SERVICIOS DE LA BANCA DE SEGUROS DE INVERSION DE SEGUROS Y DE PENSIONES) (COMISIONES)'],
            ['code' => '2.1.2.02.02.007.02', 'name' => 'OTROS SERVICIOS DISTINTOS DE LOS SEGUROS DE VIDA NCP (POLIZA DE MANEJO Y POLIZA MULTIRIESGO)'],
            ['code' => '2.1.2.02.02.007.03', 'name' => 'SERVICIO DE ARRENDAMIENTO O ALQUILER DE OTROS PRODUCTOS NCP (ALOJAMIENTO WEB - HOSTING)'],
            ['code' => '2.1.2.02.02.008.01', 'name' => 'SERVICIOS JURIDICOS Y CONTABLES'],
            ['code' => '2.1.2.02.02.008.02', 'name' => 'SERVICIOS DE TELEFONIA Y OTROS SERVICIOS DE TELECOMUNICACIONES'],
            ['code' => '2.1.2.02.02.008.03', 'name' => 'SERVICIOS DE TELECOMUNICACIONES VIA INTERNET'],
            ['code' => '2.1.2.02.02.008.04', 'name' => 'SERVICIOS DE TRANSMISION, PROGRAMACION Y DISTRIBUCION DE PROGRAMAS'],
            ['code' => '2.1.2.02.02.008.05', 'name' => 'SERVICIO DE MANTENIMIENTO, REPARACION E INSTALACION (EXCEPTO SERVICIOS DE CONSTRUCCION)'],
            ['code' => '2.1.2.02.02.009.01', 'name' => 'OTROS SERVICIOS DE LA EDUCACION Y LA FORMACION'],
            ['code' => '2.1.2.02.02.009.02', 'name' => 'SERVICIO DE EDUCACION ARTISTICA Y CULTURAL'],
            ['code' => '2.1.2.02.02.009.03', 'name' => 'SERVICIO DE EDUCACION DEPORTIVA Y RECREACION'],
            ['code' => '2.1.2.02.02.009.04', 'name' => 'OTROS TIPOS DE SERVICIOS EDUCATIVOS Y DE FORMACION NCP'],
            ['code' => '2.1.2.02.02.009.05', 'name' => 'SERVICIO DE APOYO EDUCATIVO'],
            ['code' => '2.1.2.02.02.009.06', 'name' => 'OTROS SERVICIOS DEPORTIVOS Y RECREATIVOS'],
        ];

        foreach ($codes as $code) {
            ExpenseCode::updateOrCreate(
                ['code' => $code['code']],
                ['name' => $code['name'], 'is_active' => true]
            );
        }
    }
}
