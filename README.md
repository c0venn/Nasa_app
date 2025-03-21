# Integración NASA DONKI API

Esta aplicación proporciona una API REST basada en Laravel que se integra con la API DONKI (Base de Datos de Notificaciones, Conocimiento e Información) de la NASA para obtener y analizar eventos climáticos espaciales y sus instrumentos asociados.

## Desarrollo

Este proyecto utiliza:
- Laravel 11.3
- PHP <8.x
- NASA/DONKI 3RD API (https://api.nasa.gov/DONKI)


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

## Parámetros de Fecha

Todos los endpoints (excepto `/api/nasa`) aceptan los siguientes parámetros de consulta opcionales:

- `startDate`: Fecha de inicio en formato YYYY-MM-DD
- `endDate`: Fecha de fin en formato YYYY-MM-DD

Si no se proporcionan fechas, por defecto se utilizan:
- `startDate`: 30 días antes de la fecha actual
- `endDate`: Fecha actual

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
GET /api/instruments?startDate=2025-02-01&endDate=2025-02-28
```
Devuelve todos los instrumentos utilizados en los diferentes servicios durante el período especificado.

**Ejemplo de Respuesta:**
```json
{
    "/CME": {
        "status": "success",
        "message": "Data retrieved successfully",
        "instruments": [
            "SOHO: LASCO/C2",
            "SOHO: LASCO/C3"
        ]
    },
}
```

### 3. Obtener IDs de Actividad
```http
GET /api/activityid?startDate=2025-02-01&endDate=2025-02-28
```
Devuelve los IDs de actividad asociados con cada servicio durante el período especificado.

**Ejemplo de Respuesta:**
```json
{
    "/CME": {
        "status": "success",
        "message": "Data retrieved successfully",
        "activityId": ["CME-001", "CME-002"]
    },
}
```

### 4. Obtener Estadísticas de Uso de Instrumentos
```http
GET /api/instruments-use?startDate=2025-02-01&endDate=2025-02-28
```
Devuelve el porcentaje de uso de cada instrumento en todos los servicios durante el período especificado.

**Ejemplo de Respuesta:**
```json
{
    "status": "success",
    "message": "Instruments usage percentages calculated successfully",
    "total_appearances": 30,
    "percentages": {
        "DSCOVR: PLASMAG": 0.267,
        "ACE: MAG": 0.267
    }
}
```

### 5. Obtener Desglose de Actividad por Instrumento
```http
POST /api/instrument-usage
```

**Cuerpo de la Petición:**
```json
{
    "instrument": "MODEL: SWMF",
    "startDate": "2025-02-01",
    "endDate": "2025-02-28"
}
```

**Ejemplo de Respuesta:**
```json
{
    "status": "success",
    "message": "Instrument usage percentages calculated successfully",
    "instrument-Activity": {
        "MODEL: SWMF": {
            "CME-001": 0.3,
            "SEP-001": 0.1,
            "HSS": 0.6
        }
    },
    "date_range": {
        "start_date": "2025-02-01",
        "end_date": "2025-02-28"
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

## Validación de Fechas

- Las fechas deben estar en formato YYYY-MM-DD
- La fecha de fin debe ser posterior a la fecha de inicio
- Si se proporciona una fecha inválida, se devolverá un error 400
- Las fechas son opcionales en todos los endpoints

## Formatos de Respuesta

Todos los endpoints devuelven respuestas JSON con la siguiente estructura general:
- `status`: Estado de éxito/error
- `message`: Mensaje legible para humanos
- Datos específicos del endpoint

## Colección Postman

Para importar fácilmente todos los endpoints en Postman:

1. Descarga el archivo de colección: [NASA_DONKI_Collection.json](./postman/NASA_DONKI_Collection.json)
2. En Postman, haz clic en "Import" > "Upload Files"
3. Selecciona el archivo descargado
4. La colección se importará con todos los endpoints configurados

También puedes usar este JSON directamente:

```json
{
    "info": {
        "name": "NASA DONKI API",
        "description": "Colección de endpoints para la API de NASA DONKI",
        "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
    },
    "item": [
        {
            "name": "Listar Proyectos NASA",
            "request": {
                "method": "GET",
                "url": "http://127.0.0.1:8000/api/nasa"
            }
        },
        {
            "name": "Obtener Todos los Instrumentos",
            "request": {
                "method": "GET",
                "url": "http://127.0.0.1:8000/api/instruments?startDate=2025-02-01&endDate=2025-02-28"
            }
        },
        {
            "name": "Obtener IDs de Actividad",
            "request": {
                "method": "GET",
                "url": "http://127.0.0.1:8000/api/activityid?startDate=2025-02-01&endDate=2025-02-28"
            }
        },
        {
            "name": "Obtener Estadísticas de Uso de Instrumentos",
            "request": {
                "method": "GET",
                "url": "http://127.0.0.1:8000/api/instruments-use?startDate=2025-02-01&endDate=2025-02-28"
            }
        },
        {
            "name": "Obtener Desglose de Actividad por Instrumento",
            "request": {
                "method": "POST",
                "url": "http://127.0.0.1:8000/api/instrument-usage",
                "header": [
                    {
                        "key": "Content-Type",
                        "value": "application/json"
                    }
                ],
                "body": {
                    "mode": "raw",
                    "raw": "{\n    \"instrument\": \"MODEL: SWMF\",\n    \"startDate\": \"2025-02-01\",\n    \"endDate\": \"2025-02-28\"\n}"
                }
            }
        }
    ]
}
```


