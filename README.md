## Kanan Web - Instrucciones de ejecución

### 1. Requisitos

- PHP 8.2+ con extensiones `pdo_mysql`, `openssl`, `mbstring`.
- MariaDB.
- Composer instalado en tu sistema.

### 2. Crear base de datos y tablas

Desde la carpeta del proyecto (`kanan 2`), ejecuta:

```bash
mysql -u root -p < database.sql
```

Esto creará la base `kanan_web` y todas las tablas.

### 3. Crear usuario de base de datos con mínimo privilegio

Conéctate a MariaDB como root y ejecuta (ajusta contraseña y host):

```sql
CREATE USER 'kanan_user'@'localhost' IDENTIFIED BY 'TU_PASSWORD_SEGURA';
GRANT SELECT, INSERT, UPDATE, DELETE ON kanan_web.* TO 'kanan_user'@'localhost';
FLUSH PRIVILEGES;
```

Luego edita `config/db.php` y pon el usuario y contraseña correctos.

### 4. Instalar dependencias (TCPDF)

En la raíz del proyecto:

```bash
composer install
```

Esto instalará TCPDF en `vendor/` y permitirá generar el PDF cifrado.

### 5. Crear el primer usuario

Desde la raíz del proyecto:

```bash
php create_user.php
```

Sigue las instrucciones. Se creará un usuario con un PIN de 6 dígitos (con validación de seguridad).

### 6. Levantar el servidor de desarrollo

Desde la carpeta `public/`:

```bash
cd public
php -S localhost:8000
```

Luego abre en el navegador:

- `http://localhost:8000/index.php` → Login y dashboard.

### 7. Flujo de uso

- Inicia sesión con tu `nombre de usuario` y `PIN`.
- Ingresa el código MFA: para este MVP es **123123**.
- En el dashboard puedes:
  - Registrar bitácora de salud.
  - Gestionar medicamentos.
  - Usar el botón de emergencia (links `tel:`).
  - Generar un PDF cifrado (contraseña la defines al generarlo).
  - Generar un QR de emergencia de 24h (`ver_emergencia.php?token=...`) que solo muestra tipo de sangre y alergias.

### 8. Seguridad

- Todas las consultas usan prepared statements.
- Los datos enviados al navegador se escapan con `htmlspecialchars` para evitar XSS.
- Formularios protegidos con token CSRF.
- Intentos de login fallidos se auditan y después de 5 intentos se bloquea la cuenta temporalmente.

