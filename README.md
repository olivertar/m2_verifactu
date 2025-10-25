# Orangecat Verifactuapi - Módulo de Integración VERIFACTU para Magento 2

## ⚠️ AVISO IMPORTANTE

**Este módulo NO ha sido probado en ambientes productivos y debe ser utilizado bajo la exclusiva responsabilidad de quien lo instale y use.**

El módulo se proporciona "tal cual" sin garantías de ningún tipo. Se recomienda encarecidamente realizar pruebas exhaustivas en entornos de desarrollo y staging antes de considerar su uso en producción.

## 📋 Sobre VERIFACTU

A partir de **julio de 2026**, la Agencia Tributaria española (AEAT) implementará el sistema **VERIFACTU** (anteriormente conocido como VeriFactu), que exigirá que todos los sistemas de facturación cumplan con los requisitos de trazabilidad e integridad establecidos en el reglamento.

Este módulo facilita la integración de Magento 2 con el sistema VERIFACTU para cumplir con esta obligación legal.

### ⚙️ Arquitectura de Integración

**IMPORTANTE:** Este módulo **NO se comunica directamente con la API de la AEAT**. En su lugar, utiliza el servicio intermediario **VERIFACTU API de NEMON INVOCASH** (https://verifactuapi.es), que actúa como puente entre Magento y la AEAT.

**Flujo de trabajo:**
```
Magento 2 → VERIFACTU API (NEMON INVOCASH) → AEAT
```

## 🚀 Características Principales

### Para el Cliente (Frontend)

- **Visualización de QR de Verificación:** Los clientes pueden ver el código QR de VERIFACTU directamente en:
  - Área privada de cliente (sección "Mis Pedidos" > "Ver Factura")
  - Vista de impresión de facturas
  
- **URL de Verificación:** Junto al QR, se muestra la URL para verificar la factura en la sede electrónica de la AEAT

- **Mensajes de Estado:** Información clara sobre el estado de la factura:
  - Pendiente de envío
  - Enviada y pendiente de validación AEAT
  - Confirmada por AEAT
  - Advertencias (si las hay)

### Para el Administrador (Backend)

#### Panel de Control de Facturas

- **Columna de Estado Verifactu** en el grid de facturas con estados codificados por colores:
  - 🟠 Pendiente (pending)
  - 🟡 Reintento (retry)
  - 🔵 Enviada - esperando confirmación AEAT (sent)
  - 🟢 Confirmada por AEAT (confirmed)
  - 🟠 Confirmada con advertencias (warning)
  - 🔴 Fallida (failed)

- **Columna QR Code:** Visualización de códigos QR directamente en el grid (click para ampliar)

- **Acción de Reenvío Manual:** Botón "Enviar a Verifactu" en cada factura para forzar un reenvío

- **PDFs con QR:** Los PDFs de facturas generados desde el admin incluyen automáticamente el código QR de VERIFACTU

#### Grid de Logs de API

Accesible desde **Sistema > Herramientas > Verifactu API Logs**

- Registro detallado de todas las comunicaciones con la API
- Filtros por estado (success/error/pending)
- Enlaces directos a las facturas
- Visualización de totales y errores
- Limpieza automática configurable de logs antiguos

#### Webhooks

- **Registro Automático:** Botón en la configuración para registrar el webhook en la API de VERIFACTU
- **Notificaciones en Tiempo Real:** Recepción automática de confirmaciones de la AEAT vía webhook
- **Seguridad:** Sistema de verificación de firma secreta

## 📦 Instalación

```bash

composer require orangecat/module-verifactuapi
php bin/magento module:enable Orangecat_CspWhitelist
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
php bin/magento cache:flush
```

## ⚙️ Configuración

Navega a: **Tiendas > Configuración > Orangecat > Verifactu API**

### 1. Configuración General (General Settings)

- **Activar Módulo:** Habilita/deshabilita el módulo

### 2. Credenciales de API (API Credentials)

- **Email de API:** Tu email de cuenta en VERIFACTU API
- **Contraseña de API:** Tu contraseña de API

> 💡 **Nota:** Necesitas crear una cuenta en https://verifactuapi.es para obtener estas credenciales

### 3. Información del Emisor (Emisor Information)

- **NIF:** NIF/CIF de tu empresa
- **Nombre de Empresa:** Razón social de tu empresa
- **Código Postal:** Código postal de tu empresa

### 4. Configuración de Reintentos (Retry Settings)

- **Número Máximo de Reintentos:** Cantidad de veces que se intentará enviar una factura antes de marcarla como fallida (predeterminado: 3)
- **Intervalo de Reintento (minutos):** Tiempo entre reintentos en minutos (predeterminado: 30)

### 5. Configuración de Notificaciones (Notification Settings)

- **Activar Notificaciones por Email:** Envía emails cuando una factura falla después de agotar los reintentos
- **Destinatarios de Email:** Lista de emails separados por comas
- **Remitente del Email:** Identidad del remitente

### 6. Configuración del Webhook (Webhook Configuration)

- **URL del Webhook Actual:** Muestra la URL del webhook de tu tienda
- **Botón Registrar Webhook:** Registra automáticamente el webhook en la API de VERIFACTU

### 7. Configuración de Visualización del QR (QR Display Settings)

Personaliza los mensajes mostrados a los clientes:

- **Título de la Sección QR:** Título mostrado encima del QR
- **Mensaje de Pendiente:** Para facturas pendientes o en reintento
- **Mensaje de Esperando Confirmación:** Para facturas enviadas pero no confirmadas
- **Mensaje de Advertencia:** Para facturas confirmadas con advertencias
- **Mensaje de Fallo:** Para facturas que fallaron la validación

### 8. Configuración de Depuración (Debug Settings)

- **Activar Registro Detallado:** Guarda todos los requests y responses de la API en la base de datos
- **Días de Retención de Registros:** Número de días antes de eliminar logs automáticamente (predeterminado: 30)

## 🔄 Funcionamiento del Sistema

### Proceso Automático

1. **Creación de Factura:** Cuando se crea una factura en Magento, se crea automáticamente un registro en estado "pendiente"

2. **Procesamiento por Cron:** El cron job (`*/5 * * * *` - cada 5 minutos) procesa hasta 50 facturas pendientes por ejecución

3. **Envío a VERIFACTU:** Las facturas se envían a la API de VERIFACTU con todos los datos fiscales necesarios

4. **Estado "Enviada":** La factura pasa a estado "sent" (enviada), esperando validación de la AEAT

5. **Webhook de Confirmación:** Cuando la AEAT valida la factura, VERIFACTU envía una notificación webhook con el resultado

6. **Estado Final:** La factura pasa a estado "confirmed" (confirmada), "warning" (confirmada con advertencias), o se reintenta si hay errores

### Gestión de Errores

- Si una factura falla, el sistema la marca como "retry"
- Se reintentará según la configuración (por defecto, 3 intentos con 30 minutos entre cada uno)
- Si se agotan los reintentos, la factura se marca como "failed" y se envía notificación por email
- Los administradores pueden forzar el reenvío manualmente desde el grid de facturas

## 🌍 Idiomas Soportados

El módulo incluye traducciones completas para:

- 🇪🇸 **Castellano** (es_ES)
- 🇪🇸 **Catalán** (ca_ES)
- 🇪🇸 **Gallego** (gl_ES)
- 🇪🇸 **Euskera** (eu_ES)

Las traducciones se encuentran en el directorio `i18n/`

## 🔧 Tareas Cron

El módulo registra dos tareas cron:

1. **Procesamiento de Facturas Pendientes**
   - Job: `orangecat_verifactuapi_process_pending`
   - Frecuencia: Cada 5 minutos (`*/5 * * * *`)
   - Función: Envía facturas pendientes a VERIFACTU (máximo 50 por ejecución)

2. **Limpieza de Logs Antiguos**
   - Job: `orangecat_verifactuapi_clean_logs`
   - Frecuencia: Diaria a las 2:00 AM (`0 2 * * *`)
   - Función: Elimina logs más antiguos que el período configurado

## 📊 Base de Datos

El módulo crea dos tablas:

1. **orangecat_verifactu_invoice:** Almacena el estado y datos de cada factura enviada a VERIFACTU
2. **orangecat_verifactu_api_log:** Registra todas las comunicaciones con la API (si el debug está activado)

## 🐛 Debug y Resolución de Problemas

### Activar Logs Detallados

1. Navega a la configuración del módulo
2. En "Debug Settings", activa "Activar Registro Detallado"
3. Revisa los logs en **Sistema > Herramientas > Verifactu API Logs**

### Logs del Sistema

Los logs del módulo también se escriben en:
- `var/log/system.log`
- `var/log/exception.log`

Busca líneas que contengan "Verifactu" para filtrar información relevante.

### Problemas Comunes

**Facturas que no se envían:**
- Verifica que el módulo esté habilitado
- Comprueba las credenciales de API
- Revisa que el cron de Magento esté ejecutándose correctamente
- Consulta el grid de logs de API para ver errores específicos

**Webhook no funciona:**
- Asegúrate de haber registrado el webhook desde la configuración
- Verifica que tu servidor sea accesible desde internet (no localhost)
- Comprueba los logs de API para ver si se reciben las notificaciones

**PDFs sin QR:**
- Verifica que la factura esté en estado "confirmed" o "warning"
- Comprueba que el QR se haya guardado correctamente en la base de datos

## 📝 Notas Técnicas

### Cálculo de Impuestos

El módulo agrupa automáticamente los artículos de la factura por tipo impositivo y los ajusta a las tasas válidas españolas (0%, 2%, 4%, 5%, 7.5%, 10%, 21%).

### Destinatario (Cliente)

El NIF del cliente solo se incluye en el registro si:
- El campo `customer_taxvat` contiene un valor
- El valor tiene más de 5 caracteres

### Compatibilidad

- **Magento:** 2.4.x
- **PHP:** 7.4+
- **Base de datos:** MySQL 5.7+ / MariaDB 10.2+

## 🤝 Soporte

No se brinda ningun tipo de soporte sobre el codigo de este modulo

Para soporte relacionado con la API de VERIFACTU, contacta con NEMON INVOCASH en https://verifactuapi.es

## 🔗 Enlaces Útiles

- **API VERIFACTU:** https://verifactuapi.es
- **Documentación AEAT sobre VERIFACTU:** https://sede.agenciatributaria.gob.es/
- **Normativa:** Reglamento de facturación (Real Decreto pendiente de publicación)

---

**Versión del módulo:** 1.0.0  
**Última actualización:** 2025
