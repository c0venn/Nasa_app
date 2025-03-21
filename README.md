# Integración NASA DONKI API

Esta aplicación proporciona una API REST basada en Laravel que se integra con la API DONKI (Base de Datos de Notificaciones, Conocimiento e Información) de la NASA para obtener y analizar eventos climáticos espaciales y sus instrumentos asociados.

## Configuración

1. Crea un archivo `.env` en el directorio raíz y agrega tu clave API de NASA:

API_KEY_NASA=tu_clave_api_aquí


2. Instala las dependencias:
```bash
composer install
npm install
```

3. Inicia el servidor:
```bash
Composer run dev
```

4. Acceda a la integración vía URL http://127.0.0.1:8000/ 

## Límite de Peticiones

Todos los endpoints relacionados con NASA están limitados a 30 peticiones por minuto reconocidos mediante dirección IP. Puedes acceder al Límite atravéz de los headers de la petición. Cuando se excede este límite, recibirás una respuesta 429 (Demasiadas Peticiones) con información sobre cuándo volver a intentar.

## Endpoints de la API

### 1. Listar Proyectos NASA
```http
GET /api/nasa
```
Devuelve una lista de todos los servicios DONKI de NASA que utiliza Instrumentos.

**Ejemplo de Respuesta:**
```json
[
    "/CME",
    "/HSS",
    "/IPS",
    "/FLR",
    "/SEP",
    "/MPC",
    "/RBE",
    "/WSAEnlilSimulations"
]
```

### 2. Obtener Todos los Instrumentos
```http
GET /api/instruments
```
Devuelve todos los instrumentos utilizados en los diferentes servicios con su estado de respuesta y detalles.

**Ejemplo de Respuesta:**
```json
{
    "/CME": {
        "status": "success",
        "message": "Datos recuperados exitosamente",
        "instruments": [
            "SOHO: LASCO/C2",
            "SOHO: LASCO/C3"
        ]
    },
}
```

### 3. Obtener IDs de Actividad
```http
GET /api/activityid
```
Devuelve los IDs de actividad asociados con cada servicio.

**Ejemplo de Respuesta:**
```json
{
    "/CME": {
        "status": "success",
        "message": "Datos recuperados exitosamente",
        "activityId": ["CME-001", "CME-002"]
    },
    // ... otros servicios
}
```

### 4. Obtener Estadísticas de Uso de Instrumentos
```http
GET /api/instruments-use
```
Devuelve el porcentaje de uso de cada instrumento en todos los servicios.

**Ejemplo de Respuesta:**
```json
{
    "status": "success",
    "message": "Porcentajes de uso de instrumentos calculados exitosamente",
    "total_appearances": 30,
    "percentages": {
        "DSCOVR: PLASMAG": 0.267,
        "ACE: MAG": 0.267
    }
}
```

### 5. Obtener Desglose de Actividad por Instrumento Específico
```http
POST /api/instrument-usage
```

**Cuerpo de la Petición:**
```json
{
    "instrument": "MODEL: SWMF"
}
```

**Ejemplo de Respuesta:**
```json
{
    "instrument-Activity": {
        "MODEL: SWMF": {
            "CME-001": 0.3,
            "SEP-001": 0.1,
            "HSS": 0.6
        }
    }
}
```

## Manejo de Errores

La API devuelve los siguientes códigos de estado HTTP:
- 200: Petición exitosa
- 400: Petición incorrecta (ej., parámetros faltantes)
- 429: Demasiadas peticiones (límite excedido)
- 500: Error del servidor

Ejemplo de Respuesta de Error:
```json
{
    "status": "error",
    "message": "Demasiadas peticiones. Por favor, espere antes de reintentar.",
    "retry_after": 30
}
```

## Rango de Fechas

Por defecto, la API consulta datos dentro del siguiente rango de fechas:
- Fecha de Inicio: 2025-02-01
- Fecha de Fin: 2025-02-31

## Formatos de Respuesta

Todos los endpoints devuelven respuestas JSON con la siguiente estructura general:
- `status`: Estado de éxito/error
- `message`: Mensaje legible para humanos
- Datos específicos del endpoint

## Desarrollo

Este proyecto utiliza:
- Laravel 11.3
- PHP <8.x
- NASA DONKI 3RD API (https://api.nasa.gov/DONKI)
