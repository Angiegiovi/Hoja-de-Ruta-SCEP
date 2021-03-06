<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

/**
 * Description of ReporteFinalHRController
 *
 * @author angela
 */
class ReporteFinalHRController
{

    private function getReporteQuery2()
    {
        return file_get_contents(__DIR__ . '/reporteHR2.sql');
    }

    private function getReporteQueryMultiple(array $usuarios)
    {
        $sql = '';
        foreach ($usuarios as $usuario) {
            $sql .= $sql ? "union\n" : '';
            $sql .= $this->getReporteQuery($usuario);
        }
        return $sql;
    }

    private function getReporteQuery($nombre)
    {
        $sql = file_get_contents(__DIR__ . '/reporteHR.sql');
        $sql = str_replace('Edgar Andrade', $nombre, $sql);
        $sql = str_replace('%Edgar%Andrade%',
            '%' . preg_replace('/\s+/', '%', trim($nombre)) . '%', $sql);
        return $sql;
    }

    public function index()
    {
        echo '<a href="/reporteHR/excel" target="_blank">Descargar EXCEL</a>';
    }

    public function excel()
    {
        header("Content-Type:   application/vnd.ms-excel; charset=utf-8");
        header("Content-Disposition: attachment; filename=consolidado.xls");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: private", false);

        $usuarios = [
            'Delfín Ponce',
            'Sonia Velasquez',
            'Andrés Ortega',
            'Elena mallon',
            'Wilder Herrera',
            'Angela Choque',
            'Daniel Zapana',
            'Maritza Torrez',
            'Melissa Vargas',
            'Eddy Márquez',
            'Edgar Andrade',
        ];

        $sql = $this->getReporteQuery2(); //$this->getReporteQueryMultiple($usuarios);
        $connection = DB::connection('hr');

        $connection->select("SET lc_time_names = 'es_ES';");
        $first = true;
        echo '<table>', "\n";
        foreach ($connection->select($sql) as $obj) {
            $row = [];
            foreach ($obj as $key => $value) {
                $row[utf8_decode($key)] = str_replace("\n", ' & ',
                    utf8_decode($value));
            }
            if ($first) {
                $first = false;
                echo '<tr><th>', implode('</th><th>', array_keys($row)), '</th></tr>', "\n";
            }
            echo '<tr><td>', implode('</td><td>', $row), '</td></tr>', "\n";
        }
        echo '</table>', "\n";
    }
}
