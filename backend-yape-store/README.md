# 🛒 Yape Store - Sistema de Comercio Electrónico

Plataforma de ventas en línea con autenticación de usuarios, carrito de compras, pasarela de pago Yape y panel de cliente.

---

## 📋 Descripción

**Yape Store** es un sistema web de comercio electrónico desarrollado con arquitectura frontend/backend separada (según patrón "Arquitecto Senior"):

- **Frontend**: React + Vite
- **Backend**: Node.js + Express
- **Base de datos**: MySQL (XAMPP)

### Características Principales

- ✅ Catálogo de productos con filtrado por categorías
- ✅ Diseño estilo Mercado Libre con carruseles
- ✅ Carrito de compras persistente (localStorage)
- ✅ Autenticación de usuarios (login/registro)
- ✅ Panel de cliente (perfil, pedidos, direcciones)
- ✅ Pasarela de pago Yape con QR
- ✅ Notificaciones glassmorphism
- ✅ Responsive (móvil y escritorio)
- ✅ Ubigeo completo del Perú

---

## 🛠️ Tecnologías

| Componente | Tecnología |
|------------|------------|
| Frontend | React 18, Vite, Axios, CSS Modules |
| Backend | Node.js, Express, MySQL2 |
| Base de Datos | MySQL (XAMPP) |
| Estilos | CSS3 (Glassmorphism, Mercado Libre style) |
| Icons | Bootstrap Icons |
| Mapas | ubigeo.js (Perú) |

---

## 📁 Estructura del Proyecto

```
backend-yape-store/
├── config/
│   ├── db.js              # Conexión MySQL
│   └── auth_update.sql    # SQL para auth
├── controllers/
│   ├── authController.js  # Endpoints auth
│   ├── productoController.js  # Productos y categorías
│   └── ventaController.js    # Ventas y pagos
├── routes/
│   └── api.js             # Rutas API REST
├── server.js              # Servidor Express
├── package.json
├── .env                   # Variables entorno
└── mibd.sql               # Esquema base datos

frontend-yape-store/
├── public/
│   ├── qr-yape.jpeg       # QR de pago Yape
│   ├── favicon.svg
│   └── icons.svg
├── src/
│   ├── App.jsx            # Componente principal
│   ├── App.css            # Estilos globales
│   ├── ubigeo.js          # Ubigeo Perú
│   ├── main.jsx           # Entry point
│   └── index.css
├── index.html
├── package.json
└── vite.config.js
```

---

## 🚀 Instalación y Ejecución

### Prerrequisitos

- Node.js 18+
- XAMPP (MySQL)
- NPM o Yarn

### 1. Base de Datos

1. Abrir XAMPP y activar MySQL
2. Crear base de datos `mibd`
3. Importar `backend-yape-store/mibd.sql`

```sql
-- En phpMyAdmin o línea de comandos:
CREATE DATABASE mibd;
USE mibd;
SOURCE backend-yape-store/mibd.sql;
```

### 2. Backend

```bash
cd backend-yape-store
npm install
node server.js
# Servidor corriendo en http://localhost:3001
```

### 3. Frontend

```bash
cd frontend-yape-store
npm install
npm run dev
# Aplicación en http://localhost:5173
```

---

## 📡 Endpoints API

### Productos
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/productos` | Lista todos los productos |
| GET | `/api/categorias` | Lista categorías |

### Autenticación
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| POST | `/api/auth/registrar` | Registrar nuevo usuario |
| POST | `/api/auth/login` | Iniciar sesión |
| GET | `/api/auth/perfil/:id` | Ver perfil usuario |
| PUT | `/api/auth/perfil/:id` | Actualizar perfil |

### Pedidos
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/pedidos/:id` | Lista pedidos usuario |
| GET | `/api/pedidos/detalle/:idVenta` | Detalle pedido |

### Ventas
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| POST | `/api/comprar` | Crear venta pendiente |
| POST | `/api/pagar-ficticio` | Confirmar pago Yape |
| GET | `/api/estado-pago/:idVenta` | Consultar estado |

---

## 🎨 Funcionalidades del Frontend

### Header
- Logo "JHORDCH-JO"
- Barra de búsqueda
- Navegación de categorías (deslizable)
- Carrito de compras
- Botón usuario (muestra avatar si está logueado)

### Banner Carrusel
- 3 banners animados: Oferta del día, Envío gratis, Yape
- Auto-slide cada 5 segundos
- Puntos de navegación

### Secciones de Carruseles
1. Relacionado con tus visitas
2. Elegidos para ti
3. Inspirado en tus favoritos
4. Ofertas de la semana
5. Más vendidos
6. Nuevos ingresos

Auto-deslizamiento cada 4-5 segundos (se pausa al hover).

### Tarjetas de Producto
- Imagen del producto
- Título (máx 2 líneas)
- Precio actual y precio original
- Porcentaje de descuento
- Cuotas
- Envío gratis
- Hover con elevación

### Modal de Producto
- Imagen grande
- Información del producto
- Selector de cantidad
- Agregar al carrito

### Carrito de Compras (Sidebar)
- Lista de productos
- Control de cantidad
- Eliminar productos
- Total calculado
- Proceder al pago

### Checkout (Datos de Envío)
- Datos personales (nombre, apellido, email, teléfono)
- Dirección
- Ubigeo Perú (departamento, provincia, distrito)
- Validación de formularios

### Modal de Pago Yape
- QR de pago personalizable (`/public/qr-yape.jpeg`)
- Monto a pagar
- Número de orden
- Botón "Ya pagué"
- Auto-verificación de estado de pago
- Timeout de 5 minutos

### Autenticación
- Modal de login/registro
- Validación de credenciales
- Sesión persistente (localStorage)
- require login para comprar

### Panel de Cliente
- **Mi Perfil**: Ver y editar datos
- **Mis Pedidos**: Historial de compras
- **Direcciones**: Gestión de direcciones
- Cerrar sesión

### Notificaciones
- Estilo glassmorphism
- 4 tipos: success, error, info, warning
- Barra de progreso animada
- Auto-cierre en 4 segundos

---

## 🔧 Configuración

### Variables de Entorno (.env)

```env
PORT=3001
DB_HOST=localhost
DB_USER=root
DB_PASSWORD=
DB_NAME=mibd
```

### QR de Yape

Colocar imagen QR en: `frontend-yape-store/public/qr-yape.jpeg`

### Puerto

- Backend: `3001`
- Frontend: `5173` (Vite)

---

## 📊 Base de Datos

### Tablas Principales

- **clientes**: Usuarios registrados
- **producto**: Catálogo de productos
- **categoria**: Categorías de productos
- **ventas**: Pedidos realizados
- **detalle_venta**: Items de cada pedido
- **proveedor**: Proveedores (opcional)

---

## 🧪 Pruebas

### Usuario de Prueba

```json
{
  "email": "maryori@example.com",
  "password": "123456"
}
```

### Flujo de Compra

1. Explorar productos
2. Seleccionar categoría (opcional)
3. Agregar productos al carrito
4. Proceder al pago
5. Completar datos de envío
6. Modal Yape → Escanear QR
7. Click "Ya pagué"
8. Confirmación de compra

---

## 📱 Responsive

El diseño se adapta a:
- 📱 Móvil (< 768px)
- 📱 Tablet (768px - 1024px)
- 💻 Desktop (> 1024px)

---

## 🔐 Seguridad

- Validación de inputs en frontend y backend
- Sesión de usuario en localStorage (no sensible)
- Conexión a BD con credenciales configurables

---

## 📄 Licencia

MIT License - Proyecto educativo

---

## 👨‍💻 Autor

Desarrollado por **Jhordch** - 2026

---

## ⚙️ Estado del Proyecto

| Módulo | Estado |
|--------|--------|
| Catálogo productos | ✅ Completo |
| Filtrado categorías | ✅ Completo |
| Carruseles auto-slide | ✅ Completo |
| Carrito compras | ✅ Completo |
| Checkout/envío | ✅ Completo |
| Pago Yape | ✅ Completo |
| Auth usuarios | ✅ Completo |
| Panel cliente | ✅ Completo |
| Notificaciones | ✅ Completo |
| Responsive | ✅ Completo |
| Ubigeo Perú | ✅ Completo |