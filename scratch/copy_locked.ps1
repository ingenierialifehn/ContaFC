$src = 'C:\Users\Annonymous\Documents\Fernando\Aplicaciones\PHP\ContaFC\database\Resumen VF asientos.DBF'
$dest = 'C:\Users\Annonymous\Documents\Fernando\Aplicaciones\PHP\ContaFC\tmp\resumen.dbf'
try {
    $stream = [System.IO.File]::Open($src, [System.IO.FileMode]::Open, [System.IO.FileAccess]::Read, [System.IO.FileShare]::ReadWrite)
    $out = [System.IO.File]::Create($dest)
    $stream.CopyTo($out)
    $stream.Close()
    $out.Close()
    Write-Output "Copied successfully"
} catch {
    Write-Error $_.Exception.Message
}
