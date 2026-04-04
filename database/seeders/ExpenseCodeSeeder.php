<?php

namespace Database\Seeders;

use App\Models\ExpenseCode;
use Illuminate\Database\Seeder;

class ExpenseCodeSeeder extends Seeder
{
    public function run(): void
    {
        $codes = [
            // PRESUPUESTO DE GASTOS DE FUNCIONAMIENTO - GASTOS GENERALES APROBADOS
            ['sifse_code' => '7', 'code' => '2.1.2.01.01.003.01.06', 'name' => 'OTRAS MÁQUINAS PARA USOS GENERALES Y SUS PARTES Y PIEZAS'],
            ['sifse_code' => '7', 'code' => '2.1.2.01.01.003.02.08', 'name' => 'OTRA MAQUINARIA PARA USOS ESPECIALES Y SUS PARTES Y PIEZAS'],
            ['sifse_code' => '7', 'code' => '2.1.2.01.01.003.03.01', 'name' => 'MAQUINAS PARA OFICINA Y CONTABILIDAD Y SUS PARTES Y ACCESORIOS'],
            ['sifse_code' => '7', 'code' => '2.1.2.01.01.003.03.02', 'name' => 'MAQUINARIA DE INFORMÁTICA Y SUS PARTES, PIEZAS Y ACCESORIOS'],
            ['sifse_code' => '7', 'code' => '2.1.2.01.01.003.05.02', 'name' => 'APARATOS TRANSMISORES DE TELEVISION Y RADIO: TELEVISION, VIDEO Y CAMARAS DIGITALES; TELEFONO'],
            ['sifse_code' => '7', 'code' => '2.1.2.01.01.003.05.03', 'name' => 'RADIORRECEPTORES Y RECEPTORES DE TELEVISIÓN; APARATOS PARA LA GRABACIÓN Y REPRODUCCIÓN DE SONIDO Y VIDEO; MICRÓFONOS, ALTAVOCES, AMPLIFICADORES, ETC.'],
            ['sifse_code' => '7', 'code' => '2.1.2.01.01.003.06.02', 'name' => 'INSTRUMENTOS Y APARATOS DE MEDICIÓN, VERIFICACIÓN, ANÁLISIS, DE NAVEGACIÓN Y PARA OTROS FINES (EXCEPTO INSTRUMENTOS ÓPTICOS); INSTRUMENTOS DE CONTROL DE PROCESOS INDUSTRIALES, SUS PARTES, PIEZAS Y ACCESORIOS'],
            ['sifse_code' => '7', 'code' => '2.1.2.01.01.004.01.01.02', 'name' => 'MUEBLES DE TIPO UTILIZADOS EN LA OFICINA'],
            ['sifse_code' => '7', 'code' => '2.1.2.01.01.004.01.01.04', 'name' => 'OTROS MUEBLES N.C.P.'],
            ['sifse_code' => '7', 'code' => '2.1.2.01.01.004.01.02', 'name' => 'INSTRUMENTOS MUSICALES'],
            ['sifse_code' => '7', 'code' => '2.1.2.01.01.004.01.03', 'name' => 'ARTÍCULOS DE DEPORTE'],
            ['sifse_code' => '7', 'code' => '2.1.2.01.01.005.01.01.01', 'name' => 'ANIMALES DE CRIA'],
            ['sifse_code' => '7', 'code' => '2.1.2.01.01.005.01.01.02', 'name' => 'GANADO LECHERO'],
            ['sifse_code' => '7', 'code' => '2.1.2.01.01.005.01.01.08', 'name' => 'OTROS ANIMALES QUE GENERAN PRODUCTOS EN FORMA REPETIDA'],
            ['sifse_code' => '7', 'code' => '2.1.2.01.01.005.01.02.06', 'name' => 'OTROS ÁRBOLES, CULTIVOS Y PLANTAS QUE GENERAN PRODUCTOS EN FORMA REPETIDA'],
            ['sifse_code' => '22', 'code' => '2.1.2.01.01.005.02.03.01.01', 'name' => 'PAQUETES DE SOFTWARE'],
            ['sifse_code' => '24', 'code' => '2.1.2.02.01.000', 'name' => 'AGRICULTURA, SILVICULTURA Y PRODUCTOS DE LA PESCA'],
            ['sifse_code' => '22', 'code' => '2.1.2.02.01.003.01', 'name' => 'DOTACION INSTITUCIONAL DE MATERIAL Y MEDIOS PEDAGOGICOS PARA EL APRENDIZAJE (TEXTOS, LIBROS, MATERIAL DIDACTICO, GUIAS)'],
            ['sifse_code' => '16', 'code' => '2.1.2.02.01.003.02', 'name' => 'IMPRESOS Y PUBLICACIONES'],
            ['sifse_code' => '7', 'code' => '2.1.2.02.01.003.03', 'name' => 'PAPELERÍA Y ÚTILES DE ESCRITORIO'],
            ['sifse_code' => '7', 'code' => '2.1.2.02.01.003.04', 'name' => 'OTROS ARTÍCULOS MANUFACTURADOS N.C.P.'],
            ['sifse_code' => '7', 'code' => '2.1.2.02.01.003.05', 'name' => 'ELEMENTOS DE ASEO Y CAFETERÍA Y OTROS MATERIALES'],
            ['sifse_code' => '20', 'code' => '2.1.2.02.02.005.01', 'name' => 'MANTENIMIENTO DE INFRAESTRUCTURA EDUCATIVA'],
            ['sifse_code' => '20', 'code' => '2.1.2.02.02.005.02', 'name' => 'SERVICIOS DE INSTALACIONES'],
            ['sifse_code' => '20', 'code' => '2.1.2.02.02.005.03', 'name' => 'SERVICIOS DE TERMINACION Y ACABADO DE EDIFICIOS'],
            ['sifse_code' => '26', 'code' => '2.1.2.02.02.006.01', 'name' => 'OTROS SERVICIOS DE ALOJAMIENTO'],
            ['sifse_code' => '26', 'code' => '2.1.2.02.02.006.02', 'name' => 'SERVICIOS DE SUMINISTRO DE COMIDAS'],
            ['sifse_code' => '26', 'code' => '2.1.2.02.02.006.03', 'name' => 'SERVICIO DE TRANSPORTE LOCAL Y TURISTICO DE PASAJEROS'],
            ['sifse_code' => '26', 'code' => '2.1.2.02.02.006.04', 'name' => 'SERVICIOS DE TRANSPORTE DE PASAJEROS, DIFERENTE DEL TRANSPORTE LOCAL Y TURÍSTICO DE PASAJEROS'],
            ['sifse_code' => '18', 'code' => '2.1.2.02.02.006.05', 'name' => 'SERVICIOS POSTALES Y DE MENSAJERÍA'],
            ['sifse_code' => '10', 'code' => '2.1.2.02.02.006.06', 'name' => 'SERVICIOS DE DISTRIBUCIÓN DE ELECTRICIDAD, Y SERVICIOS DE DISTRIBUCIÓN DE GAS'],
            ['sifse_code' => '9', 'code' => '2.1.2.02.02.006.07', 'name' => 'SERVICIOS DE DISTRIBUCIÓN DE AGUA (POR CUENTA PROPIA)'],
            ['sifse_code' => '18', 'code' => '2.1.2.02.02.007.01', 'name' => 'OTROS SERVICIOS FINANCIEROS NCP (EXCEPTO LOS SERVICIOS DE LA BANCA DE SEGUROS DE INVERSION DE SEGUROS Y DE PENSIONES) (COMISIONES)'],
            ['sifse_code' => '14', 'code' => '2.1.2.02.02.007.02', 'name' => 'OTROS SERVICIOS DISTINTOS DE LOS SEGUROS DE VIDA NCP (POLIZA DE MANEJO Y POLIZA MULTIRIESGO)'],
            ['sifse_code' => '8', 'code' => '2.1.2.02.02.007.03', 'name' => 'SERVICIO DE ARRENDAMIENTO O ALQUILER DE OTROS PRODUCTOS NCP (ALOJAMIENTO WEB - HOSTING)'],
            ['sifse_code' => '15', 'code' => '2.1.2.02.02.008.01', 'name' => 'SERVICIOS JURIDICOS Y CONTABLES'],
            ['sifse_code' => '11', 'code' => '2.1.2.02.02.008.02', 'name' => 'SERVICIOS DE TELEFONIA Y OTROS SERVICIOS DE TELECOMUNICACIONES'],
            ['sifse_code' => '12', 'code' => '2.1.2.02.02.008.03', 'name' => 'SERVICIOS DE TELECOMUNICACIONES VIA INTERNET'],
            ['sifse_code' => '15', 'code' => '2.1.2.02.02.008.04', 'name' => 'SERVICIOS DE TRANSMISION, PROGRAMACION Y DISTRIBUCION DE PROGRAMAS'],
            ['sifse_code' => '20', 'code' => '2.1.2.02.02.008.05', 'name' => 'SERVICIO DE MANTENIMIENTO, REPARACION E INSTALACION (EXCEPTO SERVICIOS DE CONSTRUCCION)'],
            ['sifse_code' => '26', 'code' => '2.1.2.02.02.009.01', 'name' => 'OTROS SERVICIOS DE LA EDUCACION Y LA FORMACION'],
            ['sifse_code' => '26', 'code' => '2.1.2.02.02.009.02', 'name' => 'SERVICIO DE EDUCACION ARTISTICA Y CULTURAL'],
            ['sifse_code' => '26', 'code' => '2.1.2.02.02.009.03', 'name' => 'SERVICIO DE EDUCACION DEPORTIVA Y RECREACION'],
            ['sifse_code' => '27', 'code' => '2.1.2.02.02.009.04', 'name' => 'OTROS TIPOS DE SERVICIOS EDUCATIVOS Y DE FORMACION NCP'],
            ['sifse_code' => '27', 'code' => '2.1.2.02.02.009.05', 'name' => 'SERVICIO DE APOYO EDUCATIVO'],
            ['sifse_code' => '26', 'code' => '2.1.2.02.02.009.06', 'name' => 'OTROS SERVICIOS DEPORTIVOS Y RECREATIVOS'],

            // FI - FUNCIONAMIENTO INVERSIÓN
            ['sifse_code' => '200', 'code' => '2.1.2.01.01.003.01.06', 'name' => 'FI-OTRAS MÁQUINAS PARA USOS GENERALES Y SUS PARTES Y PIEZAS'],
            ['sifse_code' => '200', 'code' => '2.1.2.01.01.003.02.08', 'name' => 'FI-OTRA MAQUINARIA PARA USOS ESPECIALES Y SUS PARTES Y PIEZAS'],
            ['sifse_code' => '200', 'code' => '2.1.2.01.01.003.03.01', 'name' => 'FI-MAQUINAS PARA OFICINA Y CONTABILIDAD Y SUS PARTES Y ACCESORIOS'],
            ['sifse_code' => '200', 'code' => '2.1.2.01.01.003.03.02', 'name' => 'FI-MAQUINARIA DE INFORMÁTICA Y SUS PARTES, PIEZAS Y ACCESORIOS'],
            ['sifse_code' => '200', 'code' => '2.1.2.01.01.003.05.02', 'name' => 'FI-APARATOS TRANSMISORES DE TELEVISION Y RADIO: TELEVISION, VIDEO Y CAMARAS DIGITALES; TELEFONO'],
            ['sifse_code' => '200', 'code' => '2.1.2.01.01.003.05.03', 'name' => 'FI-RADIORRECEPTORES Y RECEPTORES DE TELEVISIÓN; APARATOS PARA LA GRABACIÓN Y REPRODUCCIÓN DE SONIDO Y VIDEO; MICRÓFONOS, ALTAVOCES, AMPLIFICADORES, ETC.'],
            ['sifse_code' => '200', 'code' => '2.1.2.01.01.003.06.02', 'name' => 'FI-INSTRUMENTOS Y APARATOS DE MEDICIÓN, VERIFICACIÓN, ANÁLISIS, DE NAVEGACIÓN Y PARA OTROS FINES (EXCEPTO INSTRUMENTOS ÓPTICOS); INSTRUMENTOS DE CONTROL DE PROCESOS INDUSTRIALES, SUS PARTES, PIEZAS Y ACCESORIOS'],
            ['sifse_code' => '200', 'code' => '2.1.2.01.01.004.01.01.02', 'name' => 'FI-MUEBLES UTILIZADOS EN LA OFICINA'],
            ['sifse_code' => '200', 'code' => '2.1.2.01.01.004.01.01.04', 'name' => 'FI-OTROS MUEBLES N.C.P.'],
            ['sifse_code' => '200', 'code' => '2.1.2.01.01.004.01.02', 'name' => 'FI-INSTRUMENTOS MUSICALES'],
            ['sifse_code' => '200', 'code' => '2.1.2.01.01.004.01.03', 'name' => 'FI-ARTÍCULOS DE DEPORTE'],
            ['sifse_code' => '200', 'code' => '2.1.2.02.01.003', 'name' => 'FI-DOTACION INSTITUCIONAL DE MATERIAL Y MEDIOS PEDAGOGICOS PARA EL APRENDIZAJE (TEXTOS, LIBROS, MATERIAL DIDACTICO, GUIAS)'],
            ['sifse_code' => '201', 'code' => '2.1.2.02.02.005', 'name' => 'FI-MANTENIMIENTO DE INFRAESTRUCTURA EDUCATIVA'],
            ['sifse_code' => '201', 'code' => '2.1.2.02.02.008', 'name' => 'FI-SERVICIOS DE MANTENIMIENTO, REPARACION E INSTALACION (EXCEPTO SERVICIOS DE CONSTRUCCION)'],
            ['sifse_code' => '202', 'code' => '2.1.2.02.02.009', 'name' => 'FI-GASTOS DE VIAJE E INSCRIPCIÓN ACTIVIDADES PEDAGÓGICAS'],
            ['sifse_code' => '203', 'code' => '2.1.2.02.02.009', 'name' => 'FI-OTROS GASTOS DE EDUCACIÓN-PROYECTOS TRANSVERSALES'],
            ['sifse_code' => '204', 'code' => '2.1.2.02.02.009', 'name' => 'FI-OTROS GASTOS DE EDUCACIÓN-PLANES MEJORAMIENTO'],
            ['sifse_code' => '205', 'code' => '2.1.2.02.02.006', 'name' => 'FI-CONTRATACIÓN TRANSPORTE ESCOLAR'],
            ['sifse_code' => '206', 'code' => '2.1.2.02.02.006', 'name' => 'FI-ALIMENTACIÓN'],
            ['sifse_code' => '207', 'code' => '2.1.2.02.02.009', 'name' => 'FI-DESARROLLO ACTIVIDADES EXTRACURRICULARES'],
            ['sifse_code' => '208', 'code' => '2.1.2.02.02.009', 'name' => 'FI-IMPLEMENTACIÓN ESTRATEGIAS RETO CRESE'],

            // PI - PROYECTOS DE INVERSIÓN
            ['sifse_code' => '250', 'code' => '2.1.2.01.01.003.01.06', 'name' => 'PI-OTRAS MÁQUINAS PARA USOS GENERALES Y SUS PARTES Y PIEZAS'],
            ['sifse_code' => '250', 'code' => '2.1.2.01.01.003.02.08', 'name' => 'PI-OTRA MAQUINARIA PARA USOS ESPECIALES Y SUS PARTES Y PIEZAS'],
            ['sifse_code' => '250', 'code' => '2.1.2.01.01.003.03.01', 'name' => 'PI-MAQUINAS PARA OFICINA Y CONTABILIDAD Y SUS PARTES Y ACCESORIOS'],
            ['sifse_code' => '250', 'code' => '2.1.2.01.01.003.03.02', 'name' => 'PI-MAQUINARIA DE INFORMÁTICA Y SUS PARTES, PIEZAS Y ACCESORIOS'],
            ['sifse_code' => '250', 'code' => '2.1.2.01.01.003.05.02', 'name' => 'PI-APARATOS TRANSMISORES DE TELEVISION Y RADIO: TELEVISION, VIDEO Y CAMARAS DIGITALES; TELEFONO'],
            ['sifse_code' => '250', 'code' => '2.1.2.01.01.003.05.03', 'name' => 'PI-RADIORRECEPTORES Y RECEPTORES DE TELEVISIÓN; APARATOS PARA LA GRABACIÓN Y REPRODUCCIÓN DE SONIDO Y VIDEO; MICRÓFONOS, ALTAVOCES, AMPLIFICADORES, ETC.'],
            ['sifse_code' => '250', 'code' => '2.1.2.01.01.003.06.02', 'name' => 'PI-INSTRUMENTOS Y APARATOS DE MEDICIÓN, VERIFICACIÓN, ANÁLISIS, DE NAVEGACIÓN Y PARA OTROS FINES (EXCEPTO INSTRUMENTOS ÓPTICOS); INSTRUMENTOS DE CONTROL DE PROCESOS INDUSTRIALES, SUS PARTES, PIEZAS Y ACCESORIOS'],
            ['sifse_code' => '250', 'code' => '2.1.2.01.01.004.01.01.02', 'name' => 'PI-MUEBLES UTILIZADOS EN LA OFICINA'],
            ['sifse_code' => '250', 'code' => '2.1.2.01.01.004.01.01.04', 'name' => 'PI-OTROS MUEBLES N.C.P.'],
            ['sifse_code' => '250', 'code' => '2.1.2.01.01.004.01.02', 'name' => 'PI-INSTRUMENTOS MUSICALES'],
            ['sifse_code' => '250', 'code' => '2.1.2.01.01.004.01.03', 'name' => 'PI-ARTÍCULOS DE DEPORTE'],
            ['sifse_code' => '250', 'code' => '2.1.2.02.01.003', 'name' => 'PI-DOTACION INSTITUCIONAL DE MATERIAL Y MEDIOS PEDAGOGICOS PARA EL APRENDIZAJE (TEXTOS, LIBROS, MATERIAL DIDACTICO, GUIAS)'],
            ['sifse_code' => '250', 'code' => '2.1.2.02.02.009', 'name' => 'PI-DESARROLLO ACTIVIDADES EXTRACURRICULARES'],
            ['sifse_code' => '251', 'code' => '2.1.2.02.02.005', 'name' => 'PI-MANTENIMIENTO DE INFRAESTRUCTURA EDUCATIVA'],
            ['sifse_code' => '251', 'code' => '2.1.2.02.01.003', 'name' => 'PI-MATERIAL PARA MTO INFRAESTRUCTURA'],

            // RES - RESIDENCIAS ESCOLARES (Bienes)
            ['sifse_code' => '80', 'code' => '2.1.2.01.01.003.01.06', 'name' => 'RES-OTRAS MÁQUINAS PARA USOS GENERALES Y SUS PARTES Y PIEZAS'],
            ['sifse_code' => '80', 'code' => '2.1.2.01.01.003.02.08', 'name' => 'RES-OTRA MAQUINARIA PARA USOS ESPECIALES Y SUS PARTES Y PIEZAS'],
            ['sifse_code' => '80', 'code' => '2.1.2.01.01.003.03.01', 'name' => 'RES-MAQUINAS PARA OFICINA Y CONTABILIDAD Y SUS PARTES Y ACCESORIOS'],
            ['sifse_code' => '80', 'code' => '2.1.2.01.01.003.03.02', 'name' => 'RES-MAQUINARIA DE INFORMÁTICA Y SUS PARTES, PIEZAS Y ACCESORIOS'],
            ['sifse_code' => '80', 'code' => '2.1.2.01.01.003.05.02', 'name' => 'RES-APARATOS TRANSMISORES DE TELEVISION Y RADIO: TELEVISION, VIDEO Y CAMARAS DIGITALES; TELEFONO'],
            ['sifse_code' => '80', 'code' => '2.1.2.01.01.003.05.03', 'name' => 'RES-RADIORRECEPTORES Y RECEPTORES DE TELEVISIÓN; APARATOS PARA LA GRABACIÓN Y REPRODUCCIÓN DE SONIDO Y VIDEO; MICRÓFONOS, ALTAVOCES, AMPLIFICADORES, ETC.'],
            ['sifse_code' => '80', 'code' => '2.1.2.01.01.003.06.02', 'name' => 'RES-INSTRUMENTOS Y APARATOS DE MEDICIÓN, VERIFICACIÓN, ANÁLISIS, DE NAVEGACIÓN Y PARA OTROS FINES (EXCEPTO INSTRUMENTOS ÓPTICOS); INSTRUMENTOS DE CONTROL DE PROCESOS INDUSTRIALES, SUS PARTES, PIEZAS Y ACCESORIOS'],
            ['sifse_code' => '80', 'code' => '2.1.2.01.01.004.01.01.02', 'name' => 'RES-MUEBLES UTILIZADOS EN LA OFICINA'],
            ['sifse_code' => '80', 'code' => '2.1.2.01.01.004.01.01.04', 'name' => 'RES-OTROS MUEBLES N.C.P.'],
            ['sifse_code' => '80', 'code' => '2.1.2.01.01.004.01.02', 'name' => 'RES-INSTRUMENTOS MUSICALES'],
            ['sifse_code' => '80', 'code' => '2.1.2.01.01.004.01.03', 'name' => 'RES-ARTÍCULOS DE DEPORTE'],
            ['sifse_code' => '80', 'code' => '2.1.2.02.01.003', 'name' => 'RES-DOTACION INSTITUCIONAL DE MATERIAL Y MEDIOS PEDAGOGICOS PARA EL APRENDIZAJE (TEXTOS, LIBROS, MATERIAL DIDACTICO, GUIAS)'],

            // RESIDENCIAS ESCOLARES - Servicios
            ['sifse_code' => '82', 'code' => '2.1.2.02.01.003', 'name' => 'RESIDENCIAS ESCOLARES-FUNCIONAMIENTO BASICO. RESIDENCIAS ESCOLARES'],
            ['sifse_code' => '77', 'code' => '2.1.2.02.02.005', 'name' => 'RESIDENCIAS ESCOLARES-MEJORAMIENTO DE INFRAESTRUCTURA REPARACION, MODIFICACION, RESTAURACION. RESIDENCIAS ESCOLARES'],
            ['sifse_code' => '78', 'code' => '2.1.2.02.02.007', 'name' => 'RESIDENCIAS ESCOLARES-ARRENDAMIENTOS. RESIDENCIAS ESCOLARES'],
            ['sifse_code' => '79', 'code' => '2.1.2.02.02.008', 'name' => 'RESIDENCIAS ESCOLARES-CONTRATACION DE PERSONAL PARA LA PREPARACION DE ALIMENTOS'],
            ['sifse_code' => '81', 'code' => '2.1.2.02.02.006', 'name' => 'RESIDENCIAS ESCOLARES-ALIMENTACION. RESIDENCIAS ESCOLARES'],
            ['sifse_code' => '83', 'code' => '2.1.2.02.02.006', 'name' => 'RESIDENCIAS ESCOLARES-DOTACION SERVICIO DE HOSPEDAJE- RESIDENCIAS ESCOLARES'],

            // ARL ESTUDIANTES
            ['sifse_code' => '66', 'code' => '2.1.2.02.02.007', 'name' => 'ARL ESTUDIANTES'],
        ];

        foreach ($codes as $code) {
            ExpenseCode::updateOrCreate(
                ['sifse_code' => $code['sifse_code'], 'code' => $code['code']],
                ['name' => $code['name'], 'is_active' => true]
            );
        }
    }
}
