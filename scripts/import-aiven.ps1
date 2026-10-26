$ErrorActionPreference = 'Stop'

$mysql = 'C:\xampp\mysql\bin\mysql.exe'
$schema = Join-Path $PSScriptRoot '..\database\schema.sql'

if (-not (Test-Path -LiteralPath $mysql)) {
    throw "No se encontro el cliente MySQL de XAMPP en $mysql"
}

$hostName = Read-Host 'Host de Aiven'
$port = Read-Host 'Puerto de Aiven'
$database = Read-Host 'Nombre de la base de datos'
$user = Read-Host 'Usuario de Aiven'
$securePassword = Read-Host 'Contrasena de Aiven' -AsSecureString
$password = [Runtime.InteropServices.Marshal]::PtrToStringAuto(
    [Runtime.InteropServices.Marshal]::SecureStringToBSTR($securePassword)
)

$env:MYSQL_PWD = $password
try {
    Get-Content -Raw -LiteralPath $schema |
        # XAMPP ships MariaDB client, which uses --ssl instead of MySQL's --ssl-mode.
        & $mysql --host=$hostName --port=$port --user=$user --database=$database --ssl
    if ($LASTEXITCODE -ne 0) {
        throw "MySQL termino con codigo $LASTEXITCODE"
    }
    Write-Host 'Esquema importado correctamente.' -ForegroundColor Green
} finally {
    Remove-Item Env:MYSQL_PWD -ErrorAction SilentlyContinue
    $password = $null
}
