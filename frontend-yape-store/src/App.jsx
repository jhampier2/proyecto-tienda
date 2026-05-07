import { useState, useEffect, useRef, useMemo } from 'react';
import axios from 'axios';
import { UBIGEO_PERU } from './ubigeo';
import './App.css';

function App() {
  // ─── BASE URL DEL BACKEND EN RENDER ───────────────────────────────────────
  const API_URL = 'https://proyecto-tienda-7anu.onrender.com/api';

  const [productos, setProductos] = useState([]);
  const [carrito, setCarrito] = useState(() => {
    const saved = localStorage.getItem('carrito-yape');
    return saved ? JSON.parse(saved) : [];
  });
  const [productoSeleccionado, setProductoSeleccionado] = useState(null);
  const [mostrarPagoYape, setMostrarPagoYape] = useState(false);
  const [idVentaPendiente, setIdVentaPendiente] = useState(null);
  const [mostrarCarrito, setMostrarCarrito] = useState(false);
  const [errorPago, setErrorPago] = useState(null);
  const [cargandoPago, setCargandoPago] = useState(false);
  const [pasoCheckout, setPasoCheckout] = useState(1);
  const [datosComprador, setDatosComprador] = useState({
    nombres: '',
    apellidos: '',
    telefono: '',
    email: '',
    direccion: ''
  });
  const [erroresForm, setErroresForm] = useState({});
  const [busqueda, setBusqueda] = useState('');
  const [categoriaActiva, setCategoriaActiva] = useState('todas');
  const [ordenarPor, setOrdenarPor] = useState('relevancia');
  const [cargando, setCargando] = useState(true);
  const [mostrarBotonArriba, setMostrarBotonArriba] = useState(false);
  const [cantidadSeleccionada, setCantidadSeleccionada] = useState(1);
  const [categorias, setCategorias] = useState([]);
  const [mostrarFormEnvio, setMostrarFormEnvio] = useState(false);
  const [metodoEnvio, setMetodoEnvio] = useState('estandar');
  const [departamento, setDepartamento] = useState('');
  const [provincia, setProvincia] = useState('');
  const [distrito, setDistrito] = useState('');
  const [notificaciones, setNotificaciones] = useState([]);

  // Auth state
  const [usuario, setUsuario] = useState(() => {
    try {
      const saved = localStorage.getItem('usuario-yape');
      return saved ? JSON.parse(saved) : null;
    } catch (e) {
      localStorage.removeItem('usuario-yape');
      return null;
    }
  });
  const [mostrarAuth, setMostrarAuth] = useState(false);
  const [modoAuth, setModoAuth] = useState('login');
  const [mostrarPerfil, setMostrarPerfil] = useState(false);
  const [misPedidos, setMisPedidos] = useState([]);
  const [pedidoSeleccionado, setPedidoSeleccionado] = useState(null);
  const [panelTab, setPanelTab] = useState('perfil');
  const [editandoPerfil, setEditandoPerfil] = useState(false);
  const [datosPerfil, setDatosPerfil] = useState({
    nombres: '',
    apellidos: '',
    telefono: '',
    email: '',
    direccion: ''
  });
  const [guardandoPerfil, setGuardandoPerfil] = useState(false);

  // Filtrar categorías que tienen productos
  const categoriasConProductos = useMemo(() => {
    if (!Array.isArray(productos) || productos.length === 0) {
      return [{ id_categoria: 'todas', descripcion: 'Todas' }];
    }
    const categoriasConProd = new Set();
    productos.forEach(p => {
      if (p.id_categoria) {
        categoriasConProd.add(p.id_categoria);
      }
    });
    return categorias.filter(cat =>
      cat.id_categoria === 'todas' || categoriasConProd.has(cat.id_categoria)
    );
  }, [categorias, productos]);

  // Get provinces based on selected department
  const provinciasDisponibles = useMemo(() => {
    try {
      if (!departamento || !UBIGEO_PERU || !UBIGEO_PERU[departamento]) return [];
      if (!UBIGEO_PERU[departamento].provincias) return [];
      return Object.entries(UBIGEO_PERU[departamento].provincias).map(([key, val]) => ({
        key,
        nombre: val.nombre
      }));
    } catch (e) {
      return [];
    }
  }, [departamento]);

  // Get districts based on selected department and province
  const distritosDisponibles = useMemo(() => {
    try {
      if (!departamento || !provincia || !UBIGEO_PERU?.[departamento]?.provincias?.[provincia]) return [];
      return UBIGEO_PERU[departamento].provincias[provincia].distritos || [];
    } catch (e) {
      return [];
    }
  }, [departamento, provincia]);

  // Lógica de filtrado
  const productosFiltrados = useMemo(() => {
    try {
      if (!Array.isArray(productos)) return [];
      let filtrados = [...productos];

      const catActive = String(categoriaActiva);
      if (catActive !== 'todas') {
        filtrados = filtrados.filter(p =>
          p.categoria_nombre?.toLowerCase() === catActive.toLowerCase() ||
          p.id_categoria?.toString() === catActive
        );
      }

      if (busqueda) {
        filtrados = filtrados.filter(p =>
          p.nombre?.toLowerCase().includes(busqueda.toLowerCase()) ||
          p.descripcion?.toLowerCase().includes(busqueda.toLowerCase())
        );
      }

      switch (ordenarPor) {
        case 'precio-asc':
          filtrados.sort((a, b) => (a.precio || 1) - (b.precio || 1));
          break;
        case 'precio-desc':
          filtrados.sort((a, b) => (b.precio || 1) - (a.precio || 1));
          break;
        case 'nombre':
          filtrados.sort((a, b) => (a.nombre || '').localeCompare(b.nombre || ''));
          break;
        default:
          break;
      }

      return filtrados;
    } catch (e) {
      console.error("Error filtrando productos:", e);
      return [];
    }
  }, [productos, categoriaActiva, busqueda, ordenarPor]);

  useEffect(() => {
    setCargando(true);
    Promise.all([
      axios.get(`${API_URL}/productos`),
      axios.get(`${API_URL}/categorias`)
    ])
      .then(([prodRes, catRes]) => {
        if (Array.isArray(prodRes.data)) {
          setProductos(prodRes.data);
        }
        if (Array.isArray(catRes.data) && catRes.data.length > 0) {
          setCategorias([{ id_categoria: 'todas', descripcion: 'Todas' }, ...catRes.data]);
        } else {
          setCategorias([
            { id_categoria: 'todas', descripcion: 'Todas' },
            { id_categoria: 'electronica', descripcion: 'Electrónica' },
            { id_categoria: 'hogar', descripcion: 'Hogar' }
          ]);
        }
      })
      .catch(err => {
        console.error("Error cargando datos", err);
        setProductos([]);
        setCategorias([
          { id_categoria: 'todas', descripcion: 'Todas' },
          { id_categoria: 'electronica', descripcion: 'Electrónica' },
          { id_categoria: 'hogar', descripcion: 'Hogar' }
        ]);
      })
      .finally(() => setCargando(false));
  }, []);

  useEffect(() => {
    localStorage.setItem('carrito-yape', JSON.stringify(carrito));
  }, [carrito]);

  // Auto-slide banner
  useEffect(() => {
    const interval = setInterval(() => {
      setBannerIndex(prev => (prev + 1) % 3);
    }, 5000);
    return () => clearInterval(interval);
  }, []);

  // Auto-slide carruseles
  useEffect(() => {
    const carruseles = [carrusel1Ref, carrusel2Ref, carrusel3Ref, carrusel4Ref, carrusel5Ref, carrusel6Ref];
    let intervals = [];

    const startAutoSlide = () => {
      intervals = carruseles.map((ref, idx) => {
        return setInterval(() => {
          if (ref.current && !ref.current.matches(':hover')) {
            const maxScroll = ref.current.scrollWidth - ref.current.clientWidth;
            const currentScroll = ref.current.scrollLeft;
            if (currentScroll >= maxScroll - 10) {
              ref.current.scrollTo({ left: 0, behavior: 'smooth' });
            } else {
              ref.current.scrollBy({ left: 220, behavior: 'smooth' });
            }
          }
        }, 4000 + idx * 1000);
      });
    };

    startAutoSlide();
    return () => intervals.forEach(clearInterval);
  }, []);

  useEffect(() => {
    const handleScroll = () => {
      setMostrarBotonArriba(window.scrollY > 400);
    };
    window.addEventListener('scroll', handleScroll);
    return () => window.removeEventListener('scroll', handleScroll);
  }, []);

  useEffect(() => {
    let intervalo;
    let timeoutId;

    if (mostrarPagoYape && idVentaPendiente) {
      timeoutId = setTimeout(() => {
        clearInterval(intervalo);
        setMostrarPagoYape(false);
        setIdVentaPendiente(null);
        setErrorPago('Tiempo de espera agotado. Por favor, intenta nuevamente.');
      }, 300000);

      intervalo = setInterval(async () => {
        try {
          const res = await axios.get(
            `${API_URL}/estado-pago/${idVentaPendiente}?t=${new Date().getTime()}`
          );
          const estadoPago = (res.data.estado || '').toString().toLowerCase();

          if (estadoPago.includes('pagado') || estadoPago.includes('completado')) {
            clearInterval(intervalo);
            clearTimeout(timeoutId);
            setMostrarPagoYape(false);
            setCarrito([]);
            setIdVentaPendiente(null);
            setMostrarCarrito(false);
            setErrorPago(null);
            setTimeout(() => {
              showNotification('¡Pago confirmado exitosamente! Tu compra ha sido procesada.', 'success');
            }, 300);
          } else if (estadoPago.includes('rechazado') || estadoPago.includes('fallido')) {
            clearInterval(intervalo);
            clearTimeout(timeoutId);
            setMostrarPagoYape(false);
            setIdVentaPendiente(null);
            setErrorPago('El pago fue rechazado. Intenta nuevamente.');
          }
        } catch (error) {
          console.error('Error verificando estado del pago:', error);
        }
      }, 3000);
    }

    return () => {
      clearInterval(intervalo);
      clearTimeout(timeoutId);
    };
  }, [mostrarPagoYape, idVentaPendiente]);

  const agregarAlCarrito = (producto, cantidad = cantidadSeleccionada) => {
    const existe = carrito.find(item => item.id_producto === producto.id_producto);
    if (existe) {
      setCarrito(carrito.map(item =>
        item.id_producto === producto.id_producto
          ? { ...existe, cantidad: existe.cantidad + cantidad }
          : item
      ));
    } else {
      setCarrito([...carrito, { ...producto, cantidad }]);
    }
    setProductoSeleccionado(null);
    setMostrarCarrito(true);
    setCantidadSeleccionada(1);
  };

  const eliminarDelCarrito = (idProducto) => {
    setCarrito(carrito.filter(item => item.id_producto !== idProducto));
  };

  const actualizarCantidad = (idProducto, nuevaCantidad) => {
    if (nuevaCantidad < 1) {
      eliminarDelCarrito(idProducto);
      return;
    }
    setCarrito(carrito.map(item =>
      item.id_producto === idProducto
        ? { ...item, cantidad: nuevaCantidad }
        : item
    ));
  };

  const limpiarCarrito = () => {
    if (window.confirm('¿Estás seguro de vaciar el carrito?')) {
      setCarrito([]);
    }
  };

  const irArriba = () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const calcularTotal = () => {
    let total = 0;
    carrito.forEach(item => {
      total += item.cantidad * (Number(item.precio) || 1);
    });
    return total;
  };

  const validarFormulario = () => {
    const errores = {};

    if (!datosComprador.nombre.trim()) errores.nombre = 'El nombre es obligatorio';
    if (!datosComprador.email.trim()) errores.email = 'El email es obligatorio';
    else if (!/\S+@\S+\.\S+/.test(datosComprador.email)) errores.email = 'Email inválido';
    if (!datosComprador.telefono.trim()) errores.telefono = 'El teléfono es obligatorio';
    else if (!/^\d{9}$/.test(datosComprador.telefono.replace(/\s/g, ''))) errores.telefono = 'Ingresa 9 dígitos';
    if (!datosComprador.dni.trim()) errores.dni = 'El DNI es obligatorio';
    else if (!/^\d{8}$/.test(datosComprador.dni)) errores.dni = 'DNI debe tener 8 dígitos';

    setErroresForm(errores);
    return Object.keys(errores).length === 0;
  };

  // Función para mostrar notificaciones glassmorphism
  const showNotification = (mensaje, tipo = 'success') => {
    const id = Date.now();
    setNotificaciones(prev => [...prev, { id, mensaje, tipo }]);
    setTimeout(() => {
      setNotificaciones(prev => prev.filter(n => n.id !== id));
    }, 4000);
  };

  // ─── AUTH FUNCTIONS ────────────────────────────────────────────────────────
  const handleLogin = async (e) => {
    e.preventDefault();
    const email = e.target.email.value;
    const password = e.target.password.value;

    try {
      const res = await axios.post(`${API_URL}/auth/login`, { email, password });
      if (res.data.success) {
        setUsuario(res.data.usuario);
        localStorage.setItem('usuario-yape', JSON.stringify(res.data.usuario));
        setMostrarAuth(false);
        showNotification('¡Bienvenido ' + res.data.usuario.nombres + '!', 'success');
      }
    } catch (error) {
      showNotification(error.response?.data?.error || 'Error al iniciar sesión', 'error');
    }
  };

  const handleRegister = async (e) => {
    e.preventDefault();
    const datos = {
      nombres: e.target.nombres.value,
      apellidos: e.target.apellidos.value,
      telefono: e.target.telefono.value,
      email: e.target.email.value,
      password: e.target.password.value,
      direccion: e.target.direccion?.value || ''
    };

    try {
      const res = await axios.post(`${API_URL}/auth/registrar`, datos);
      if (res.data.success) {
        setUsuario(res.data.usuario);
        localStorage.setItem('usuario-yape', JSON.stringify(res.data.usuario));
        setMostrarAuth(false);
        showNotification('¡Registro exitoso! Bienvenido.', 'success');
      }
    } catch (error) {
      showNotification(error.response?.data?.error || 'Error al registrar', 'error');
    }
  };

  const handleLogout = () => {
    setUsuario(null);
    localStorage.removeItem('usuario-yape');
    setMostrarPerfil(false);
    showNotification('Sesión cerrada correctamente', 'info');
  };

  const cargarMisPedidos = async () => {
    if (!usuario || !usuario.id_cliente) {
      console.log('No hay usuario o id_cliente', usuario);
      return;
    }
    try {
      console.log('Cargando pedidos para cliente:', usuario.id_cliente);
      const res = await axios.get(`${API_URL}/pedidos/${usuario.id_cliente}`);
      console.log('Respuesta pedidos:', res.data);
      if (res.data && res.data.pedidos) {
        setMisPedidos(res.data.pedidos);
      } else {
        setMisPedidos([]);
      }
    } catch (error) {
      console.error('Error al cargar pedidos:', error);
      setMisPedidos([]);
    }
  };

  const cargarDetallePedido = async (idVenta) => {
    try {
      const res = await axios.get(
        `${API_URL}/pedidos/detalle/${idVenta}?idCliente=${usuario.id_cliente}`
      );
      if (res.data.success) {
        setPedidoSeleccionado(res.data.pedido);
      }
    } catch (error) {
      showNotification('Error al cargar detalle del pedido', 'error');
    }
  };

  useEffect(() => {
    if (mostrarPerfil && usuario) {
      setDatosPerfil({
        nombres: usuario.nombres || '',
        apellidos: usuario.apellidos || '',
        telefono: usuario.telefono || '',
        email: usuario.email || '',
        direccion: usuario.direccion || ''
      });
    }
  }, [mostrarPerfil, usuario]);

  useEffect(() => {
    if (mostrarPerfil && panelTab === 'pedidos' && usuario) {
      cargarMisPedidos();
    }
  }, [mostrarPerfil, panelTab, usuario]);

  const guardarPerfil = async () => {
    setGuardandoPerfil(true);
    try {
      const res = await axios.put(`${API_URL}/auth/perfil/${usuario.id_cliente}`, {
        nombres: datosPerfil.nombres,
        apellidos: datosPerfil.apellidos,
        telefono: datosPerfil.telefono,
        direccion: datosPerfil.direccion
      });
      if (res.data.success) {
        const usuarioActualizado = { ...usuario, ...datosPerfil };
        setUsuario(usuarioActualizado);
        localStorage.setItem('usuario-yape', JSON.stringify(usuarioActualizado));
        setEditandoPerfil(false);
        showNotification('Perfil actualizado correctamente', 'success');
      }
    } catch (error) {
      showNotification('Error al guardar perfil', 'error');
    } finally {
      setGuardandoPerfil(false);
    }
  };

  const iniciarCompra = async () => {
    if (!usuario) {
      showNotification('Por favor, inicia sesión para continuar', 'warning');
      setModoAuth('login');
      setMostrarAuth(true);
      return;
    }

    const errores = {};
    if (!datosComprador.nombres?.trim()) errores.nombres = 'Nombres requeridos';
    if (!datosComprador.apellidos?.trim()) errores.apellidos = 'Apellidos requeridos';
    if (!datosComprador.telefono?.trim()) errores.telefono = 'Teléfono requerido';
    if (!datosComprador.direccion?.trim()) errores.direccion = 'Dirección requerida';
    if (!departamento) errores.departamento = 'Selecciona un departamento';
    if (!provincia) errores.provincia = 'Selecciona una provincia';
    if (!distrito) errores.distrito = 'Selecciona un distrito';

    if (Object.keys(errores).length > 0) {
      setErroresForm(errores);
      return;
    }

    setMostrarFormEnvio(false);
    setCargandoPago(true);
    setErrorPago(null);

    try {
      console.log('Iniciando compra, total:', calcularTotal());

      const res = await axios.post(`${API_URL}/comprar`, {
        carrito,
        total: calcularTotal(),
        idCliente: usuario?.id_cliente,
        comprador: {
          nombre: datosComprador.nombres,
          telefono: datosComprador.telefono,
          email: datosComprador.email,
          direccion: datosComprador.direccion,
          departamento,
          provincia,
          distrito
        }
      });

      console.log('Respuesta comprar:', res.data);

      if (res.data && res.data.idVenta) {
        setIdVentaPendiente(res.data.idVenta);
        setPasoCheckout(2);
        setMostrarPagoYape(true);
      } else {
        throw new Error('Respuesta inválida del servidor');
      }
    } catch (error) {
      console.error('Error al iniciar compra:', error);
      setErrorPago('Error: ' + (error.response?.data?.error || error.message));
    } finally {
      setCargandoPago(false);
    }
  };

  const confirmarEnvio = async () => {
    const errores = {};

    if (!datosComprador.direccion.trim()) errores.direccion = 'La dirección es obligatoria';
    if (!departamento) errores.departamento = 'Selecciona un departamento';
    if (!provincia) errores.provincia = 'Selecciona una provincia';
    if (!distrito) errores.distrito = 'Selecciona un distrito';

    if (Object.keys(errores).length > 0) {
      setErroresForm(errores);
      return;
    }

    setMostrarFormEnvio(false);
    setCargandoPago(true);
    setErrorPago(null);

    try {
      const res = await axios.post(`${API_URL}/comprar`, {
        carrito,
        total: calcularTotal(),
        idCliente: usuario?.id_cliente,
        comprador: {
          nombre: datosComprador.nombres,
          telefono: datosComprador.telefono,
          email: datosComprador.email,
          direccion: datosComprador.direccion,
          departamento,
          provincia,
          distrito
        }
      });

      if (res.data && res.data.idVenta) {
        setIdVentaPendiente(res.data.idVenta);
        setPasoCheckout(2);
        setMostrarPagoYape(true);
      } else {
        throw new Error('Respuesta inválida del servidor');
      }
    } catch (error) {
      console.error('Error al iniciar compra:', error);
      setErrorPago('Error al procesar la compra. Verifica tu conexión e intenta nuevamente.');
    } finally {
      setCargandoPago(false);
    }
  };

  const confirmarPagoYape = async () => {
    console.log('Confirmando pago, idVenta:', idVentaPendiente);
    setCargandoPago(true);
    setErrorPago(null);

    try {
      if (!idVentaPendiente) {
        throw new Error('No hay orden pendiente');
      }

      const res = await axios.post(`${API_URL}/pagar-ficticio`, {
        idVenta: idVentaPendiente
      });

      console.log('Respuesta pagar-ficticio:', res.data);

      if (res.data && res.data.success) {
        setMostrarPagoYape(false);
        setIdVentaPendiente(null);

        setCarrito([]);
        localStorage.removeItem('carrito-yape');

        showNotification(
          `¡Pago procesado exitosamente!\n\nNúmero de orden: ${res.data.idVenta}`,
          'success'
        );

        setPasoCheckout(0);
        setDatosComprador({
          nombres: '',
          apellidos: '',
          telefono: '',
          email: '',
          direccion: ''
        });
        setDepartamento('');
        setProvincia('');
        setDistrito('');
      } else {
        throw new Error(res.data?.error || 'No se pudo procesar el pago');
      }
    } catch (error) {
      console.error('Error al procesar pago:', error);
      setErrorPago(error.message || 'Error al procesar el pago. Intenta nuevamente.');
    } finally {
      setCargandoPago(false);
    }
  };

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setDatosComprador({
      ...datosComprador,
      [name]: value
    });
    if (erroresForm[name]) {
      setErroresForm({
        ...erroresForm,
        [name]: undefined
      });
    }
  };

  const handleEnvioChange = (e) => {
    const { name, value } = e.target;
    if (name === 'departamento') setDepartamento(value);
    if (name === 'provincia') setProvincia(value);
    if (name === 'distrito') setDistrito(value);
  };

  // Refs para los carruseles
  const carrusel1Ref = useRef(null);
  const carrusel2Ref = useRef(null);
  const carrusel3Ref = useRef(null);
  const carrusel4Ref = useRef(null);
  const carrusel5Ref = useRef(null);
  const carrusel6Ref = useRef(null);
  const categoriasNavRef = useRef(null);
  const [bannerIndex, setBannerIndex] = useState(0);

  // Función para deslizar carrusel
  const deslizarCarrusel = (ref, direccion) => {
    if (ref.current) {
      ref.current.scrollBy({
        left: direccion * 300,
        behavior: 'smooth'
      });
    }
  };

  // Función para deslizar categorías
  const deslizarCategorias = (direccion) => {
    if (categoriasNavRef.current) {
      categoriasNavRef.current.scrollBy({
        left: direccion * 150,
        behavior: 'smooth'
      });
    }
  };

  // ─── RENDER TARJETA ────────────────────────────────────────────────────────
  // Si la imagen no empieza con "http", se asume que es una ruta local del
  // servidor y se le antepone la URL base del backend en Render.
  const BACKEND_BASE = 'https://proyecto-tienda-7anu.onrender.com';

  const resolverImagen = (imagen_url) => {
    if (!imagen_url) return `${BACKEND_BASE}/uploads/sponsors/logos/default.png`;
    if (imagen_url.startsWith('http://') || imagen_url.startsWith('https://')) {
      return imagen_url;
    }
    // Ruta local del servidor → anteponer base del backend
    return `${BACKEND_BASE}${imagen_url.startsWith('/') ? '' : '/'}${imagen_url}`;
  };

  const renderTarjetaMercadoLibre = (prod) => {
    const rutaImagen = resolverImagen(prod.imagen_url);
    const precioBase = Number(prod.precio) || 1;
    const descuento = ((prod.id_producto * 7) % 40) + 50;
    const precioOriginal = (precioBase / (1 - descuento / 100)).toFixed(2);

    return (
      <div key={prod.id_producto} className="card-meli" onClick={() => setProductoSeleccionado(prod)}>
        <div className="card-meli-img">
          <img src={rutaImagen} alt={prod.nombre} />
        </div>
        <div className="card-meli-info">
          <h3 className="card-meli-titulo">{prod.nombre}</h3>
          <div className="card-meli-precios">
            <span className="card-meli-precio-actual">S/ {precioBase.toFixed(2)}</span>
            <span className="card-meli-precio-original">S/ {precioOriginal}</span>
          </div>
          <div className="card-meli-descuento">-{descuento}% OFF</div>
          <div className="card-meli-cuotas">en 12 cuotas de S/ {(precioBase / 12).toFixed(2)}</div>
          <div className="card-meli-envio">
            <span className="envio-gratis-meli"><i className="bi bi-truck"></i> Llega mañana</span>
          </div>
        </div>
      </div>
    );
  };

  const verMasCategoria = (categoria) => {
    showNotification(`Sección "${categoria}" próxima implementación`, 'info');
  };

  return (
    <div className="store-container">
      {/* Header */}
      <header className="store-header">
        <div className="header-top">
          <div className="logo-container">
            <h1 className="logo">JHORDCH-JO</h1>
            <span className="envio-gratis-badge"><i className="bi bi-truck"></i> Envío gratis</span>
          </div>

          <div className="search-bar">
            <input
              type="text"
              placeholder="Buscar productos..."
              value={busqueda}
              onChange={(e) => setBusqueda(e.target.value)}
            />
            <button className="btn-buscar"><i className="bi bi-search"></i></button>
          </div>

          <div className="header-actions">
            <button className="btn-icon"><i className="bi bi-heart"></i></button>
            <button className="btn-carrito-header" onClick={() => setMostrarCarrito(true)}>
              <i className="bi bi-bag"></i>
              {carrito.length > 0 && (
                <span className="carrito-count">{carrito.reduce((acc, item) => acc + item.cantidad, 0)}</span>
              )}
            </button>
            {usuario ? (
              <button className="btn-user-logged" onClick={() => { setMostrarPerfil(true); setPanelTab('perfil'); }}>
                <div className="user-avatar-mini">{usuario.nombres?.charAt(0).toUpperCase()}</div>
                <span className="user-name-mini">{usuario.nombres}</span>
              </button>
            ) : (
              <button className="btn-icon" onClick={() => { setModoAuth('login'); setMostrarAuth(true); }}>
                <i className="bi bi-person"></i>
              </button>
            )}
          </div>
        </div>

        <div className="categorias-wrapper">
          <button className="cat-flecha cat-flecha-izq" onClick={() => deslizarCategorias(-1)}>
            <i className="bi bi-chevron-left"></i>
          </button>
          <nav className="categorias-nav" ref={categoriasNavRef}>
            {categoriasConProductos.map(cat => {
              const catId = cat.id_categoria || cat.id;
              const catNombre = cat.descripcion || cat.nombre;
              const isActive = String(categoriaActiva) === String(catId) ||
                (categoriaActiva === 'todas' && catId === 'todas');
              return (
                <button
                  key={catId}
                  className={`cat-btn ${isActive ? 'active' : ''}`}
                  onClick={() => setCategoriaActiva(catId)}
                >
                  {catNombre}
                </button>
              );
            })}
          </nav>
          <button className="cat-flecha cat-flecha-der" onClick={() => deslizarCategorias(1)}>
            <i className="bi bi-chevron-right"></i>
          </button>
        </div>
      </header>

      {/* Banner Promocional */}
      <div className="banner-container">
        <div className="banner-carousel">
          <div className={`banner-slide ${bannerIndex === 0 ? 'active' : ''}`}>
            <div className="banner-meli banner-oferta">
              <div className="banner-content-meli">
                <span className="banner-badge-meli">OFERTA DEL DÍA</span>
                <h2>Hasta 90% OFF</h2>
                <p>En productos seleccionados</p>
                <button className="banner-btn-meli">Ver ofertas</button>
              </div>
              <div className="banner-icon-meli">
                <i className="bi bi-lightning-charge-fill"></i>
              </div>
            </div>
          </div>
          <div className={`banner-slide ${bannerIndex === 1 ? 'active' : ''}`}>
            <div className="banner-meli banner-envio">
              <div className="banner-content-meli">
                <span className="banner-badge-meli">ENVÍO GRÁTIS</span>
                <h2>En pedidos desde S/49</h2>
                <p>Solo hoy en productos marketplace</p>
                <button className="banner-btn-meli">Ver más</button>
              </div>
              <div className="banner-icon-meli">
                <i className="bi bi-truck"></i>
              </div>
            </div>
          </div>
          <div className={`banner-slide ${bannerIndex === 2 ? 'active' : ''}`}>
            <div className="banner-meli banner-yape">
              <div className="banner-content-meli">
                <span className="banner-badge-meli">PAGA CON YAPE</span>
                <h2>20% DCTO extra</h2>
                <p>Con tu código QR de Yape</p>
                <button className="banner-btn-meli">Aplicar código</button>
              </div>
              <div className="banner-icon-meli">
                <span className="yape-icon">Y</span>
              </div>
            </div>
          </div>
        </div>
        <div className="banner-dots">
          <span className={`banner-dot ${bannerIndex === 0 ? 'active' : ''}`} onClick={() => setBannerIndex(0)}></span>
          <span className={`banner-dot ${bannerIndex === 1 ? 'active' : ''}`} onClick={() => setBannerIndex(1)}></span>
          <span className={`banner-dot ${bannerIndex === 2 ? 'active' : ''}`} onClick={() => setBannerIndex(2)}></span>
        </div>
      </div>

      {/* Loading State */}
      {cargando && (
        <div className="loading-productos">
          <div className="spinner-grande"></div>
          <p>Cargando productos...</p>
        </div>
      )}

      {/* Catálogo Dinámico */}
      {!cargando && (
        <>
          {String(categoriaActiva).toLowerCase() === 'todas' && !busqueda && (
            <div className="mercado-secciones">
              <section className="seccion-categoria">
                <div className="seccion-header">
                  <h2>Relacionado con tus visitas</h2>
                  <button className="ver-mas" onClick={() => verMasCategoria('Relacionado con tus visitas')}>Ver más</button>
                </div>
                <div className="carrusel-contenedor">
                  <button className="carrusel-flecha carrusel-flecha-izq" onClick={() => deslizarCarrusel(carrusel1Ref, -1)}>
                    <i className="bi bi-chevron-left"></i>
                  </button>
                  <div ref={carrusel1Ref} className="carrusel-horizontal">
                    {productos.slice(0, 10).map(prod => renderTarjetaMercadoLibre(prod))}
                  </div>
                  <button className="carrusel-flecha carrusel-flecha-der" onClick={() => deslizarCarrusel(carrusel1Ref, 1)}>
                    <i className="bi bi-chevron-right"></i>
                  </button>
                </div>
              </section>

              <section className="seccion-categoria">
                <div className="seccion-header">
                  <h2>Elegidos para ti</h2>
                  <button className="ver-mas" onClick={() => verMasCategoria('Elegidos para ti')}>Ver más</button>
                </div>
                <div className="carrusel-contenedor">
                  <button className="carrusel-flecha carrusel-flecha-izq" onClick={() => deslizarCarrusel(carrusel2Ref, -1)}>
                    <i className="bi bi-chevron-left"></i>
                  </button>
                  <div ref={carrusel2Ref} className="carrusel-horizontal">
                    {productos.slice(10, 20).map(prod => renderTarjetaMercadoLibre(prod))}
                  </div>
                  <button className="carrusel-flecha carrusel-flecha-der" onClick={() => deslizarCarrusel(carrusel2Ref, 1)}>
                    <i className="bi bi-chevron-right"></i>
                  </button>
                </div>
              </section>

              <section className="seccion-categoria">
                <div className="seccion-header">
                  <h2>Inspirado en tus favoritos</h2>
                  <button className="ver-mas" onClick={() => verMasCategoria('Inspirado en tus favoritos')}>Ver más</button>
                </div>
                <div className="carrusel-contenedor">
                  <button className="carrusel-flecha carrusel-flecha-izq" onClick={() => deslizarCarrusel(carrusel3Ref, -1)}>
                    <i className="bi bi-chevron-left"></i>
                  </button>
                  <div ref={carrusel3Ref} className="carrusel-horizontal">
                    {productos.slice(20, 30).map(prod => renderTarjetaMercadoLibre(prod))}
                  </div>
                  <button className="carrusel-flecha carrusel-flecha-der" onClick={() => deslizarCarrusel(carrusel3Ref, 1)}>
                    <i className="bi bi-chevron-right"></i>
                  </button>
                </div>
              </section>

              <section className="seccion-categoria seccion-ofertas">
                <div className="seccion-header">
                  <h2><i className="bi bi-lightning-charge-fill"></i> Ofertas de la Semana</h2>
                  <button className="ver-mas" onClick={() => verMasCategoria('Ofertas')}>Ver todas</button>
                </div>
                <div className="carrusel-contenedor">
                  <button className="carrusel-flecha carrusel-flecha-izq" onClick={() => deslizarCarrusel(carrusel4Ref, -1)}>
                    <i className="bi bi-chevron-left"></i>
                  </button>
                  <div ref={carrusel4Ref} className="carrusel-horizontal">
                    {[...productos].reverse().slice(0, 8).map(prod => renderTarjetaMercadoLibre(prod))}
                  </div>
                  <button className="carrusel-flecha carrusel-flecha-der" onClick={() => deslizarCarrusel(carrusel4Ref, 1)}>
                    <i className="bi bi-chevron-right"></i>
                  </button>
                </div>
              </section>

              <section className="seccion-categoria">
                <div className="seccion-header">
                  <h2><i className="bi bi-graph-up-arrow"></i> Más Vendidos</h2>
                  <button className="ver-mas" onClick={() => verMasCategoria('Populares')}>Ver ranking</button>
                </div>
                <div className="carrusel-contenedor">
                  <button className="carrusel-flecha carrusel-flecha-izq" onClick={() => deslizarCarrusel(carrusel5Ref, -1)}>
                    <i className="bi bi-chevron-left"></i>
                  </button>
                  <div ref={carrusel5Ref} className="carrusel-horizontal">
                    {productos.slice(5, 15).map(prod => renderTarjetaMercadoLibre(prod))}
                  </div>
                  <button className="carrusel-flecha carrusel-flecha-der" onClick={() => deslizarCarrusel(carrusel5Ref, 1)}>
                    <i className="bi bi-chevron-right"></i>
                  </button>
                </div>
              </section>

              <section className="seccion-categoria">
                <div className="seccion-header">
                  <h2><i className="bi bi-stars"></i> Nuevos Ingresos</h2>
                  <button className="ver-mas" onClick={() => verMasCategoria('Nuevos')}>Ver más</button>
                </div>
                <div className="carrusel-contenedor">
                  <button className="carrusel-flecha carrusel-flecha-izq" onClick={() => deslizarCarrusel(carrusel6Ref, -1)}>
                    <i className="bi bi-chevron-left"></i>
                  </button>
                  <div ref={carrusel6Ref} className="carrusel-horizontal">
                    {productos.slice(30, 40).map(prod => renderTarjetaMercadoLibre(prod))}
                  </div>
                  <button className="carrusel-flecha carrusel-flecha-der" onClick={() => deslizarCarrusel(carrusel6Ref, 1)}>
                    <i className="bi bi-chevron-right"></i>
                  </button>
                </div>
              </section>
            </div>
          )}

          {(String(categoriaActiva).toLowerCase() !== 'todas' || busqueda) && (
            <div className="grilla-simple">
              {productosFiltrados.length === 0 ? (
                <div className="sin-productos">
                  <i className="bi bi-inbox"></i>
                  <h3>No hay productos en esta categoría</h3>
                  <p>Próximamente se agregarán productos</p>
                </div>
              ) : (
                <>
                  <div className="resultados-simple">
                    <span>{productosFiltrados.length} productos encontrados</span>
                  </div>
                  <div className="grilla-temu">
                    {productosFiltrados.map(prod => renderTarjetaMercadoLibre(prod))}
                  </div>
                </>
              )}
            </div>
          )}
        </>
      )}

      {/* Botón Ir Arriba */}
      {mostrarBotonArriba && (
        <button className="btn-ir-arriba" onClick={irArriba}>
          <i className="bi bi-arrow-up"></i>
        </button>
      )}

      {/* Modal de Producto */}
      {productoSeleccionado && (
        <div className="modal-overlay">
          <div className="modal-producto-temu">
            <button className="btn-cerrar" onClick={() => setProductoSeleccionado(null)}>
              <i className="bi bi-x-lg"></i>
            </button>
            <div className="modal-img-wrapper">
              <img src={resolverImagen(productoSeleccionado.imagen_url)} alt="Producto" />
            </div>
            <div className="modal-info">
              {(() => {
                const precioBase = Number(productoSeleccionado.precio) || 1;
                const descuento = ((productoSeleccionado.id_producto * 7) % 40) + 50;
                const precioOriginal = (precioBase / (1 - descuento / 100)).toFixed(2);
                const ahorro = (precioOriginal - precioBase).toFixed(2);
                return (
                  <>
                    <div className="modal-badges">
                      <span className="badge-modal">-{descuento}%</span>
                      <span className="badge-modal flash"><i className="bi bi-lightning-charge-fill"></i> Oferta Flash</span>
                    </div>
                    <h2>{productoSeleccionado.nombre}</h2>
                    <p className="descripcion">{productoSeleccionado.descripcion}</p>

                    <div className="precio-destacado-container">
                      <div className="precio-row">
                        <span className="precio-gigante">S/ {precioBase.toFixed(2)}</span>
                        <span className="precio-tachado-modal">S/ {precioOriginal}</span>
                      </div>
                      <span className="ahorro">Ahorras S/ {ahorro}</span>
                    </div>
                  </>
                );
              })()}

              <div className="envio-modal">
                <span><i className="bi bi-truck"></i> Envío gratis</span>
                <span><i className="bi bi-star-fill"></i> 4.8 (2,345 reseñas)</span>
              </div>

              <div className="cantidad-selector-modal">
                <span className="label-cantidad">Cantidad:</span>
                <div className="control-cantidad">
                  <button onClick={() => setCantidadSeleccionada(Math.max(1, cantidadSeleccionada - 1))}>-</button>
                  <span>{cantidadSeleccionada}</span>
                  <button onClick={() => setCantidadSeleccionada(cantidadSeleccionada + 1)}>+</button>
                </div>
              </div>

              <button className="btn-agregar-temu" onClick={() => agregarAlCarrito(productoSeleccionado)}>
                Añadir al Carrito - S/ {(cantidadSeleccionada * (Number(productoSeleccionado.precio) || 1)).toFixed(2)}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Sidebar del Carrito */}
      {mostrarCarrito && (
        <div className="carrito-sidebar">
          <div className="carrito-header">
            <h2>{pasoCheckout === 1 ? 'Tu Carrito' : 'Datos de Envío'}</h2>
            <button className="btn-cerrar-carrito" onClick={() => {
              setMostrarCarrito(false);
              setPasoCheckout(1);
              setErrorPago(null);
            }}>
              <i className="bi bi-x-lg"></i>
            </button>
          </div>

          {pasoCheckout === 1 && (
            <>
              <div className="carrito-header-actions">
                {carrito.length > 0 && (
                  <button className="btn-limpiar" onClick={limpiarCarrito}>
                    <i className="bi bi-trash"></i> Vaciar carrito
                  </button>
                )}
              </div>
              <div className="carrito-items">
                {carrito.length === 0 ? (
                  <div className="carrito-vacio">
                    <p><i className="bi bi-bag"></i> El carrito está vacío</p>
                    <button className="btn-seguir-comprando" onClick={() => setMostrarCarrito(false)}>
                      Seguir comprando
                    </button>
                  </div>
                ) : null}
                {carrito.map(item => {
                  const precioItem = Number(item.precio) || 1;
                  return (
                    <div key={item.id_producto} className="item-sidebar">
                      <img src={resolverImagen(item.imagen_url)} alt={item.nombre} />
                      <div className="item-detalles">
                        <p className="item-nombre">{item.nombre}</p>
                        <div className="item-precio-row">
                          <strong>S/ {(item.cantidad * precioItem).toFixed(2)}</strong>
                          <span className="item-precio-unitario">S/ {precioItem.toFixed(2)} c/u</span>
                        </div>
                        <div className="item-controles">
                          <div className="cantidad-mini-control">
                            <button onClick={() => actualizarCantidad(item.id_producto, item.cantidad - 1)}>-</button>
                            <span>{item.cantidad}</span>
                            <button onClick={() => actualizarCantidad(item.id_producto, item.cantidad + 1)}>+</button>
                          </div>
                          <button className="btn-eliminar" onClick={() => eliminarDelCarrito(item.id_producto)}>
                            Eliminar
                          </button>
                        </div>
                      </div>
                    </div>
                  );
                })}
              </div>
              {carrito.length > 0 && (
                <div className="carrito-footer">
                  <div className="carrito-resumen">
                    <span>Productos: {carrito.reduce((acc, i) => acc + i.cantidad, 0)}</span>
                    <h3>Total: S/ {calcularTotal().toFixed(2)}</h3>
                  </div>
                  <button className="btn-pagar-yape" onClick={() => setPasoCheckout(2)}>
                    Proceder al Pago
                  </button>
                </div>
              )}
            </>
          )}

          {pasoCheckout === 2 && (
            <div className="form-checkout">
              <div className="form-section-title">Datos Personales</div>

              <div className="form-grupo">
                <label>Nombres <span className="required">*</span></label>
                <input
                  type="text"
                  name="nombres"
                  value={datosComprador.nombres}
                  onChange={handleInputChange}
                  placeholder="Ej: Juan"
                  className={erroresForm.nombres ? 'input-error' : ''}
                />
                {erroresForm.nombres && <span className="error-text">{erroresForm.nombres}</span>}
              </div>

              <div className="form-grupo">
                <label>Apellidos <span className="required">*</span></label>
                <input
                  type="text"
                  name="apellidos"
                  value={datosComprador.apellidos}
                  onChange={handleInputChange}
                  placeholder="Ej: Pérez"
                  className={erroresForm.apellidos ? 'input-error' : ''}
                />
                {erroresForm.apellidos && <span className="error-text">{erroresForm.apellidos}</span>}
              </div>

              <div className="form-row">
                <div className="form-grupo">
                  <label>Email <span className="required">*</span></label>
                  <input
                    type="email"
                    name="email"
                    value={datosComprador.email}
                    onChange={handleInputChange}
                    placeholder="Ej: juan@email.com"
                    className={erroresForm.email ? 'input-error' : ''}
                  />
                  {erroresForm.email && <span className="error-text">{erroresForm.email}</span>}
                </div>

                <div className="form-grupo">
                  <label>Teléfono <span className="required">*</span></label>
                  <input
                    type="tel"
                    name="telefono"
                    value={datosComprador.telefono}
                    onChange={handleInputChange}
                    placeholder="999888777"
                    maxLength="9"
                    className={erroresForm.telefono ? 'input-error' : ''}
                  />
                  {erroresForm.telefono && <span className="error-text">{erroresForm.telefono}</span>}
                </div>
              </div>

              <div className="form-divider"></div>
              <div className="form-section-title">Dirección de Envío</div>

              <div className="form-grupo">
                <label>Dirección <span className="required">*</span></label>
                <textarea
                  name="direccion"
                  value={datosComprador.direccion}
                  onChange={handleInputChange}
                  placeholder="Ej: Av. Lima 123, departamento 45"
                  rows="3"
                  className={erroresForm.direccion ? 'input-error' : ''}
                />
                {erroresForm.direccion && <span className="error-text">{erroresForm.direccion}</span>}
              </div>

              <div className="form-grupo">
                <label>Departamento <span className="required">*</span></label>
                <select
                  name="departamento"
                  value={departamento}
                  onChange={handleEnvioChange}
                  className={erroresForm.departamento ? 'input-error' : ''}
                >
                  <option value="">Selecciona departamento</option>
                  {Object.keys(UBIGEO_PERU).map(key => (
                    <option key={key} value={key}>{UBIGEO_PERU[key].nombre}</option>
                  ))}
                </select>
                {erroresForm.departamento && <span className="error-text">{erroresForm.departamento}</span>}
              </div>

              {departamento && (
                <div className="form-grupo">
                  <label>Provincia <span className="required">*</span></label>
                  <select
                    name="provincia"
                    value={provincia}
                    onChange={handleEnvioChange}
                    className={erroresForm.provincia ? 'input-error' : ''}
                  >
                    <option value="">Selecciona provincia</option>
                    {provinciasDisponibles.map(p => (
                      <option key={p.key} value={p.key}>{p.nombre}</option>
                    ))}
                  </select>
                  {erroresForm.provincia && <span className="error-text">{erroresForm.provincia}</span>}
                </div>
              )}

              {provincia && (
                <div className="form-grupo">
                  <label>Distrito <span className="required">*</span></label>
                  <select
                    name="distrito"
                    value={distrito}
                    onChange={handleEnvioChange}
                    className={erroresForm.distrito ? 'input-error' : ''}
                  >
                    <option value="">Selecciona distrito</option>
                    {distritosDisponibles.map(d => (
                      <option key={d} value={d}>{d}</option>
                    ))}
                  </select>
                  {erroresForm.distrito && <span className="error-text">{erroresForm.distrito}</span>}
                </div>
              )}

              {errorPago && (
                <div className="error-pago">
                  <span><i className="bi bi-exclamation-triangle"></i> {errorPago}</span>
                </div>
              )}

              <div className="form-buttons">
                <button className="btn-volver" onClick={() => setPasoCheckout(1)}>
                  ← Volver
                </button>
                <button
                  className="btn-pagar-yape"
                  onClick={iniciarCompra}
                  disabled={cargandoPago}
                >
                  {cargandoPago ? 'Procesando...' : `Pagar S/ ${calcularTotal().toFixed(2)}`}
                </button>
              </div>
            </div>
          )}
        </div>
      )}

      {/* Modal de Pago Yape */}
      {mostrarPagoYape && (
        <div className="modal-overlay yape-overlay">
          <div className="yape-modal-modern">
            <button className="btn-cerrar-modern" onClick={() => {
              setMostrarPagoYape(false);
              setIdVentaPendiente(null);
              setErrorPago(null);
            }}>
              <i className="bi bi-x-lg"></i>
            </button>

            <div className="yape-header-modern">
              <div className="yape-logo-modern">
                <div className="yape-icon-circle">
                  <span>Y</span>
                </div>
                <div className="yape-brand">
                  <h2>Yape</h2>
                  <span className="yape-powered">Pago seguro</span>
                </div>
              </div>
              <div className="yape-status-badge">
                <div className="pulse-dot"></div>
                <span>Esperando pago</span>
              </div>
            </div>

            <div className="yape-amount-section">
              <p className="yape-label">Total a pagar</p>
              <h1 className="yape-amount-modern">S/ {calcularTotal().toFixed(2)}</h1>
              <p className="yape-order">Orden #{idVentaPendiente}</p>
            </div>

            <div className="yape-qr-section">
              <div className="qr-frame-modern">
                <div className="qr-glow"></div>
                <img
                  src="/qr-yape.jpeg"
                  alt="QR Yape"
                  className="qr-image-modern"
                />
              </div>
              <div className="qr-hint-modern">
                <i className="bi bi-phone"></i>
                <span>Abre Yape y escanea este código</span>
              </div>
            </div>

            <div className="yape-steps-modern">
              <div className="step-modern active">
                <div className="step-icon-modern"><i className="bi bi-phone"></i></div>
                <span>Abrir App</span>
              </div>
              <div className="step-connector"></div>
              <div className="step-modern">
                <div className="step-icon-modern"><i className="bi bi-qr-code-scan"></i></div>
                <span>Escanear</span>
              </div>
              <div className="step-connector"></div>
              <div className="step-modern">
                <div className="step-icon-modern"><i className="bi bi-check2-circle"></i></div>
                <span>Confirmar</span>
              </div>
            </div>

            <div className="yape-loading-modern">
              <div className="spinner-modern"></div>
              <span className="loading-text">Verificando pago automáticamente...</span>
            </div>

            <button
              className="btn-yape-pagado"
              onClick={confirmarPagoYape}
              disabled={cargandoPago}
            >
              {cargandoPago ? 'Procesando...' : 'Ya pagué'}
            </button>

            {errorPago && (
              <div className="error-pago-modern">
                <i className="bi bi-exclamation-circle"></i>
                <span>{errorPago}</span>
              </div>
            )}

            <button className="btn-cancel-modern" onClick={() => {
              setMostrarPagoYape(false);
              setIdVentaPendiente(null);
              setErrorPago(null);
            }}>
              Cancelar operación
            </button>
          </div>
        </div>
      )}

      {/* Formulario Flotante de Envío */}
      {mostrarFormEnvio && (
        <div className="modal-overlay">
          <div className="envio-flotante">
            <button className="btn-cerrar" onClick={() => setMostrarFormEnvio(false)}>
              <i className="bi bi-x-lg"></i>
            </button>

            <div className="envio-header">
              <h2><i className="bi bi-box-seam"></i> Datos de Envío</h2>
              <p className="envio-subtitle">Completa la información para el envío</p>
            </div>

            <div className="envio-body">
              <div className="metodo-envio">
                <h3>Método de envío</h3>
                <div className="envio-opciones">
                  <label className={`envio-opcion ${metodoEnvio === 'estandar' ? 'active' : ''}`}>
                    <input
                      type="radio"
                      name="metodoEnvio"
                      value="estandar"
                      checked={metodoEnvio === 'estandar'}
                      onChange={(e) => setMetodoEnvio(e.target.value)}
                    />
                    <div className="opcion-content">
                      <span className="opcion-icon"><i className="bi bi-truck"></i></span>
                      <div>
                        <strong>Estándar</strong>
                        <p>3-5 días hábiles</p>
                      </div>
                      <span className="opcion-precio">Gratis</span>
                    </div>
                  </label>

                  <label className={`envio-opcion ${metodoEnvio === 'express' ? 'active' : ''}`}>
                    <input
                      type="radio"
                      name="metodoEnvio"
                      value="express"
                      checked={metodoEnvio === 'express'}
                      onChange={(e) => setMetodoEnvio(e.target.value)}
                    />
                    <div className="opcion-content">
                      <span className="opcion-icon"><i className="bi bi-lightning-charge-fill"></i></span>
                      <div>
                        <strong>Express</strong>
                        <p>1-2 días hábiles</p>
                      </div>
                      <span className="opcion-precio">S/ 15.00</span>
                    </div>
                  </label>
                </div>
              </div>

              <div className="form-grupo">
                <label>Dirección completa *</label>
                <textarea
                  name="direccion"
                  value={datosComprador.direccion}
                  onChange={handleInputChange}
                  placeholder="Ej: Av. Lima 123, Departamento 45, Urbanización San Miguel"
                  rows="3"
                  className={erroresForm.direccion ? 'input-error' : ''}
                />
                {erroresForm.direccion && <span className="error-text">{erroresForm.direccion}</span>}
              </div>

              <div className="ubicacion-grid">
                <div className="form-grupo">
                  <label>Departamento *</label>
                  <select
                    name="departamento"
                    value={departamento}
                    onChange={(e) => {
                      handleEnvioChange(e);
                      setDepartamento(e.target.value);
                      setProvincia('');
                      setDistrito('');
                    }}
                    className={erroresForm.departamento ? 'input-error' : ''}
                  >
                    <option value="">Seleccionar</option>
                    {Object.entries(UBIGEO_PERU).map(([key, depto]) => (
                      <option key={key} value={key}>{depto.nombre}</option>
                    ))}
                  </select>
                  {erroresForm.departamento && <span className="error-text">{erroresForm.departamento}</span>}
                </div>

                <div className="form-grupo">
                  <label>Provincia *</label>
                  <select
                    name="provincia"
                    value={provincia}
                    onChange={(e) => {
                      handleEnvioChange(e);
                      setProvincia(e.target.value);
                      setDistrito('');
                    }}
                    className={erroresForm.provincia ? 'input-error' : ''}
                  >
                    <option value="">Seleccionar</option>
                    {provinciasDisponibles.map((prov) => (
                      <option key={prov.key} value={prov.key}>{prov.nombre}</option>
                    ))}
                  </select>
                  {erroresForm.provincia && <span className="error-text">{erroresForm.provincia}</span>}
                </div>

                <div className="form-grupo">
                  <label>Distrito *</label>
                  <select
                    name="distrito"
                    value={distrito}
                    onChange={(e) => { handleEnvioChange(e); setDistrito(e.target.value); }}
                    className={erroresForm.distrito ? 'input-error' : ''}
                  >
                    <option value="">Seleccionar</option>
                    {distritosDisponibles.map((dist, idx) => {
                      const value = dist
                        .toLowerCase()
                        .normalize('NFD')
                        .replace(/[\u0300-\u036f]/g, '')
                        .replace(/ /g, '-')
                        .replace(/ñ/g, 'n');
                      return (
                        <option key={idx} value={value}>{dist}</option>
                      );
                    })}
                  </select>
                  {erroresForm.distrito && <span className="error-text">{erroresForm.distrito}</span>}
                </div>
              </div>

              <div className="form-grupo">
                <label>Referencia (opcional)</label>
                <input
                  type="text"
                  name="referencia"
                  value={datosComprador.referencia || ''}
                  onChange={handleInputChange}
                  placeholder="Ej: Frente al parque, casa de portón azul"
                />
              </div>

              <div className="form-row">
                <div className="form-grupo">
                  <label>Código Postal</label>
                  <input
                    type="text"
                    name="codigoPostal"
                    value={datosComprador.codigoPostal || ''}
                    onChange={handleInputChange}
                    placeholder="Ej: 15074"
                    maxLength="5"
                  />
                </div>

                <div className="form-grupo">
                  <label>Número de contacto *</label>
                  <input
                    type="tel"
                    name="telefono"
                    value={datosComprador.telefono}
                    onChange={handleInputChange}
                    placeholder="999888777"
                    maxLength="9"
                    className={erroresForm.telefono ? 'input-error' : ''}
                  />
                  {erroresForm.telefono && <span className="error-text">{erroresForm.telefono}</span>}
                </div>
              </div>
            </div>

            <div className="envio-footer">
              <div className="envio-resumen">
                <span>Productos: {carrito.reduce((acc, i) => acc + i.cantidad, 0)}</span>
                <span className="envio-total">
                  Total: S/ {(calcularTotal() + (metodoEnvio === 'express' ? 15 : 0)).toFixed(2)}
                </span>
              </div>
              <button
                className="btn-confirmar-envio"
                onClick={confirmarEnvio}
                disabled={cargandoPago}
              >
                {cargandoPago ? 'Procesando...' : 'Confirmar y Pagar'}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Modal de Auth */}
      {mostrarAuth && (
        <div className="modal-overlay" onClick={() => setMostrarAuth(false)}>
          <div className="auth-modal" onClick={e => e.stopPropagation()}>
            <button className="btn-cerrar-modal" onClick={() => setMostrarAuth(false)}>
              <i className="bi bi-x-lg"></i>
            </button>

            <div className="auth-header">
              <div className="auth-logo">
                <span className="logo-yape">Y</span>
              </div>
              <h2>{modoAuth === 'login' ? 'Iniciar Sesión' : 'Crear Cuenta'}</h2>
              <p>{modoAuth === 'login' ? 'Bienvenido de vuelta' : 'Regístrate para comprar'}</p>
            </div>

            <form onSubmit={modoAuth === 'login' ? handleLogin : handleRegister} className="auth-form">
              {modoAuth === 'register' && (
                <>
                  <div className="form-row">
                    <div className="form-grupo">
                      <label>Nombres *</label>
                      <input type="text" name="nombres" required placeholder="Tu nombre" />
                    </div>
                    <div className="form-grupo">
                      <label>Apellidos *</label>
                      <input type="text" name="apellidos" required placeholder="Tu apellido" />
                    </div>
                  </div>
                  <div className="form-grupo">
                    <label>Teléfono *</label>
                    <input type="tel" name="telefono" required placeholder="999888777" maxLength="9" />
                  </div>
                </>
              )}
              <div className="form-grupo">
                <label>Email *</label>
                <input type="email" name="email" required placeholder="tu@email.com" />
              </div>
              <div className="form-grupo">
                <label>Password *</label>
                <input type="password" name="password" required placeholder="••••••••" minLength="6" />
              </div>
              {modoAuth === 'register' && (
                <div className="form-grupo">
                  <label>Dirección</label>
                  <input type="text" name="direccion" placeholder="Tu dirección (opcional)" />
                </div>
              )}
              <button type="submit" className="btn-auth-submit">
                {modoAuth === 'login' ? 'Iniciar Sesión' : 'Crear Cuenta'}
              </button>
            </form>

            <div className="auth-footer">
              <p>
                {modoAuth === 'login' ? '¿No tienes cuenta? ' : '¿Ya tienes cuenta? '}
                <button
                  className="btn-switch-auth"
                  onClick={() => setModoAuth(modoAuth === 'login' ? 'register' : 'login')}
                >
                  {modoAuth === 'login' ? 'Regístrate' : 'Inicia sesión'}
                </button>
              </p>
            </div>
          </div>
        </div>
      )}

      {/* Modal de Panel de Cliente */}
      {mostrarPerfil && usuario && (
        <div className="modal-overlay" onClick={() => { setMostrarPerfil(false); setEditandoPerfil(false); setPedidoSeleccionado(null); }}>
          <div className="panel-cliente" onClick={e => e.stopPropagation()}>
            <button className="btn-cerrar-modal" onClick={() => { setMostrarPerfil(false); setEditandoPerfil(false); setPedidoSeleccionado(null); }}>
              <i className="bi bi-x-lg"></i>
            </button>

            <div className="panel-sidebar">
              <div className="panel-user-info">
                <div className="panel-avatar">{usuario.nombres?.charAt(0).toUpperCase()}{usuario.apellidos?.charAt(0).toUpperCase()}</div>
                <h3>{usuario.nombres} {usuario.apellidos}</h3>
                <p>{usuario.email}</p>
              </div>

              <nav className="panel-nav">
                <button className={`panel-nav-item ${panelTab === 'perfil' ? 'active' : ''}`} onClick={() => { setPanelTab('perfil'); setEditandoPerfil(false); }}>
                  <i className="bi bi-person"></i>
                  <span>Mi Perfil</span>
                </button>
                <button className={`panel-nav-item ${panelTab === 'pedidos' ? 'active' : ''}`} onClick={() => { setPanelTab('pedidos'); setPedidoSeleccionado(null); }}>
                  <i className="bi bi-bag-check"></i>
                  <span>Mis Pedidos</span>
                </button>
                <button className={`panel-nav-item ${panelTab === 'direcciones' ? 'active' : ''}`} onClick={() => setPanelTab('direcciones')}>
                  <i className="bi bi-geo-alt"></i>
                  <span>Direcciones</span>
                </button>
                <button className="panel-nav-item logout" onClick={handleLogout}>
                  <i className="bi bi-box-arrow-right"></i>
                  <span>Cerrar Sesión</span>
                </button>
              </nav>
            </div>

            <div className="panel-content">
              {panelTab === 'perfil' && (
                <div className="panel-section">
                  <div className="panel-header">
                    <h2>Mi Perfil</h2>
                    {!editandoPerfil && (
                      <button className="btn-editar-perfil" onClick={() => setEditandoPerfil(true)}>
                        <i className="bi bi-pencil"></i> Editar
                      </button>
                    )}
                  </div>

                  {editandoPerfil ? (
                    <div className="perfil-edicion">
                      <div className="form-row">
                        <div className="form-grupo">
                          <label>Nombres</label>
                          <input type="text" value={datosPerfil.nombres} onChange={(e) => setDatosPerfil({ ...datosPerfil, nombres: e.target.value })} />
                        </div>
                        <div className="form-grupo">
                          <label>Apellidos</label>
                          <input type="text" value={datosPerfil.apellidos} onChange={(e) => setDatosPerfil({ ...datosPerfil, apellidos: e.target.value })} />
                        </div>
                      </div>
                      <div className="form-row">
                        <div className="form-grupo">
                          <label>Teléfono</label>
                          <input type="tel" value={datosPerfil.telefono} onChange={(e) => setDatosPerfil({ ...datosPerfil, telefono: e.target.value })} maxLength="9" />
                        </div>
                        <div className="form-grupo">
                          <label>Email</label>
                          <input type="email" value={datosPerfil.email} disabled />
                        </div>
                      </div>
                      <div className="form-grupo">
                        <label>Dirección</label>
                        <textarea value={datosPerfil.direccion} onChange={(e) => setDatosPerfil({ ...datosPerfil, direccion: e.target.value })} rows="2" />
                      </div>
                      <div className="perfil-acciones">
                        <button className="btn-cancelar-edicion" onClick={() => { setEditandoPerfil(false); setDatosPerfil({ nombres: usuario.nombres, apellidos: usuario.apellidos, telefono: usuario.telefono, email: usuario.email, direccion: usuario.direccion }); }}>
                          Cancelar
                        </button>
                        <button className="btn-guardar-perfil" onClick={guardarPerfil} disabled={guardandoPerfil}>
                          {guardandoPerfil ? 'Guardando...' : 'Guardar Cambios'}
                        </button>
                      </div>
                    </div>
                  ) : (
                    <div className="perfil-vista">
                      <div className="perfil-dato">
                        <span className="perfil-label">Nombres</span>
                        <span className="perfil-valor">{usuario.nombres}</span>
                      </div>
                      <div className="perfil-dato">
                        <span className="perfil-label">Apellidos</span>
                        <span className="perfil-valor">{usuario.apellidos}</span>
                      </div>
                      <div className="perfil-dato">
                        <span className="perfil-label">Teléfono</span>
                        <span className="perfil-valor">{usuario.telefono || 'No registrado'}</span>
                      </div>
                      <div className="perfil-dato">
                        <span className="perfil-label">Email</span>
                        <span className="perfil-valor">{usuario.email}</span>
                      </div>
                      <div className="perfil-dato">
                        <span className="perfil-label">Dirección</span>
                        <span className="perfil-valor">{usuario.direccion || 'No registrada'}</span>
                      </div>
                    </div>
                  )}
                </div>
              )}

              {panelTab === 'pedidos' && (
                <div className="panel-section">
                  <div className="panel-header">
                    <h2>Mis Pedidos</h2>
                  </div>

                  {pedidoSeleccionado ? (
                    <div className="detalle-pedido">
                      <button className="btn-volver-pedidos" onClick={() => setPedidoSeleccionado(null)}>
                        <i className="bi bi-arrow-left"></i> Volver a pedidos
                      </button>
                      <div className="detalle-pedido-header">
                        <div>
                          <span className="detalle-pedido-id">Pedido #{pedidoSeleccionado.id_venta}</span>
                          <span className="detalle-pedido-fecha">{new Date(pedidoSeleccionado.fecha).toLocaleDateString()}</span>
                        </div>
                        <span className={`estado-badge ${pedidoSeleccionado.estado?.toLowerCase()}`}>{pedidoSeleccionado.estado}</span>
                      </div>
                      <div className="detalle-pedido-info">
                        <div className="info-row">
                          <span>Método de pago:</span>
                          <span>{pedidoSeleccionado.metodo_pago || 'Yape'}</span>
                        </div>
                        <div className="info-row">
                          <span>Total:</span>
                          <span className="info-total">S/ {pedidoSeleccionado.total}</span>
                        </div>
                      </div>
                      <div className="detalle-productos">
                        <h4>Productos</h4>
                        {pedidoSeleccionado.detalles?.map((prod, idx) => (
                          <div key={idx} className="producto-pedido">
                            <img src={resolverImagen(prod.imagen_url)} alt={prod.nombre} />
                            <div className="producto-info">
                              <span className="producto-nombre">{prod.nombre}</span>
                              <span className="producto-cantidad">Cantidad: {prod.cantidad}</span>
                            </div>
                            <span className="producto-subtotal">S/ {prod.subtotal}</span>
                          </div>
                        ))}
                      </div>
                    </div>
                  ) : misPedidos.length === 0 ? (
                    <div className="pedidos-vacio">
                      <i className="bi bi-bag-x"></i>
                      <p>No tienes pedidos aún</p>
                      <button className="btn-ir-shopping" onClick={() => { setMostrarPerfil(false); }}>Empezar a Comprar</button>
                    </div>
                  ) : (
                    <div className="lista-pedidos">
                      {misPedidos.map(pedido => (
                        <div key={pedido.id_venta} className="pedido-card" onClick={() => cargarDetallePedido(pedido.id_venta)}>
                          <div className="pedido-card-header">
                            <span className="pedido-card-id">#{pedido.id_venta}</span>
                            <span className={`estado-badge ${pedido.estado?.toLowerCase()}`}>{pedido.estado}</span>
                          </div>
                          <div className="pedido-card-body">
                            <span className="pedido-card-fecha">{new Date(pedido.fecha).toLocaleDateString()}</span>
                            <span className="pedido-card-total">S/ {pedido.total}</span>
                          </div>
                          <div className="pedido-card-footer">
                            <span className="ver-detalle">Ver detalle <i className="bi bi-arrow-right"></i></span>
                          </div>
                        </div>
                      ))}
                    </div>
                  )}
                </div>
              )}

              {panelTab === 'direcciones' && (
                <div className="panel-section">
                  <div className="panel-header">
                    <h2>Mis Direcciones</h2>
                  </div>
                  {usuario.direccion ? (
                    <div className="direccion-card">
                      <div className="direccion-icon">
                        <i className="bi bi-house"></i>
                      </div>
                      <div className="direccion-info">
                        <span className="direccion-tipo">Dirección Principal</span>
                        <span className="direccion-texto">{usuario.direccion}</span>
                      </div>
                      <button className="btn-editar-direccion" onClick={() => { setPanelTab('perfil'); setEditandoPerfil(true); }}>
                        <i className="bi bi-pencil"></i>
                      </button>
                    </div>
                  ) : (
                    <div className="direcciones-vacio">
                      <i className="bi bi-geo-alt"></i>
                      <p>No tienes direcciones guardadas</p>
                      <button className="btn-agregar-direccion" onClick={() => { setPanelTab('perfil'); setEditandoPerfil(true); }}>
                        <i className="bi bi-plus"></i> Agregar Dirección
                      </button>
                    </div>
                  )}
                </div>
              )}
            </div>
          </div>
        </div>
      )}

      {/* Notificaciones Glassmorphism */}
      <div className="notificaciones-container">
        {notificaciones.map(notif => (
          <div key={notif.id} className={`notificacion-glass ${notif.tipo}`}>
            <div className="notificacion-icon">
              {notif.tipo === 'success' ? '✓' : notif.tipo === 'error' ? '✕' : notif.tipo === 'warning' ? '⚠' : 'ℹ'}
            </div>
            <div className="notificacion-content">
              <p>{notif.mensaje}</p>
            </div>
            <button
              className="notificacion-close"
              onClick={() => setNotificaciones(prev => prev.filter(n => n.id !== notif.id))}
            >
              ✕
            </button>
            <div className="notificacion-progress"></div>
          </div>
        ))}
      </div>
    </div>
  );
}

export default App;
