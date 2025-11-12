<?php 

 // 🔹 Variable PHP con el nombre corto del usuario actual
 $thenomcorto = "Sandbox"; 
 $dot  = "1407180000";
 $id_rts = "299";

?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<title>Menciones estables - con comentarios</title>
<style>
  body { font-family: Arial, sans-serif; margin: 20px; }
  .editor {
    width: 480px;
    min-height: 120px;
    border: 1px solid #ccc;
    padding: 10px;
    border-radius: 6px;
    font-size: 16px;
    outline: none;
    white-space: pre-wrap;
    color: black; /* Texto normal en negro */
  }
  .mention {
    color: blue;                  /* Menciones en azul */
    background: rgba(0,0,0,0);
    padding: 0 2px;
    border-radius: 3px;
    user-select: none;            /* No se puede seleccionar parte de la mención */
  }
  .suggestions {
    position: absolute;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    box-shadow: 0 6px 18px rgba(0,0,0,.08);
    max-height: 180px;
    overflow-y: auto;
    display: none;                /* Oculto por defecto */
    width: 320px;
    z-index: 10;
  }
  .suggestions div { padding: 10px 12px; cursor: pointer; }
  .suggestions div:hover, .suggestions div.active { background: #007bff; color: #fff; }
</style>
</head>
<body>
<h2>Menciones con teclado y mouse (con comentarios)</h2>

<!-- Área editable -->
<div id="editor" class="editor" contenteditable="true" spellcheck="false"></div>
<!-- Contenedor de sugerencias -->
<div id="suggestions" class="suggestions"></div>

<button id="btnGuardar">Guardar comentario</button>

<script>
/* ============================================================
VARIABLES Y REFERENCIAS BÁSICAS
============================================================ */

 // 🔹 PHP imprime aquí el valor directamente dentro del JS
  const currentUser = "<?php echo $thenomcorto; ?>";
  const dot = "<?php echo $dot; ?>";
  const id_rts = "<?php echo $id_rts; ?>";

const editor = document.getElementById('editor');          // Área de texto editable (donde se escriben los comentarios)
const suggestions = document.getElementById('suggestions'); // Contenedor donde se muestran las sugerencias de menciones (@)
const btnGuardar = document.getElementById('btnGuardar');   // Botón "Guardar" del comentario

// Variables de control
let users = [];        // Lista completa de usuarios obtenidos desde get_users.php
let filtered = [];     // Lista filtrada según lo que el usuario escribe después del "@"
let activeIndex = -1;  // Índice actual de la sugerencia seleccionada con teclas ↑ o ↓
let savedRange = null; // Guarda la posición actual del cursor dentro del editor
let mentionsList = []; // Lista de menciones actuales (iniciales de usuarios mencionados)


/* ============================================================
   FUNCIONES DE CURSOR Y POSICIÓN
   ============================================================ */

// Guarda la posición actual del cursor en el editor
function saveCursorPosition() {
  const sel = window.getSelection();
  if (sel.rangeCount > 0) savedRange = sel.getRangeAt(0).cloneRange();
}

// Restaura la posición del cursor guardada
function restoreCursorPosition() {
  if (!savedRange) return;
  const sel = window.getSelection();
  sel.removeAllRanges();
  sel.addRange(savedRange);
}

// Coloca el cursor justo después de un nodo específico
function placeCaretAfter(node) {
  const sel = window.getSelection();
  const r = document.createRange();
  r.setStartAfter(node);
  r.collapse(true);
  sel.removeAllRanges();
  sel.addRange(r);
  saveCursorPosition();
}

// Obtiene la posición del cursor en base a la cantidad de caracteres antes del caret
function getCaretOffset(root) {
  const sel = window.getSelection();
  if (!sel.rangeCount) return 0;
  const range = sel.getRangeAt(0);
  const pre = range.cloneRange();
  pre.selectNodeContents(root);
  pre.setEnd(range.endContainer, range.endOffset);
  return pre.toString().length;
}

// Crea un rango entre dos posiciones de texto (inicio y fin)
function rangeFromOffsets(root, start, end) {
  const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, null);
  const range = document.createRange();
  let acc = 0, startNode = null, startOffset = 0, endNode = null, endOffset = 0, node;
  while ((node = walker.nextNode())) {
    const nextAcc = acc + node.nodeValue.length;
    if (startNode === null && start >= acc && start <= nextAcc) {
      startNode = node; startOffset = start - acc;
    }
    if (endNode === null && end >= acc && end <= nextAcc) {
      endNode = node; endOffset = end - acc; break;
    }
    acc = nextAcc;
  }
  if (!startNode) { startNode = root; startOffset = 0; }
  if (!endNode) { endNode = startNode; endOffset = startOffset; }
  range.setStart(startNode, startOffset);
  range.setEnd(endNode, endOffset);
  return range;
}


/* ============================================================
   🧹 FUNCIÓN PARA LIMPIAR EL ESTILO DEL TEXTO (color negro)
   ============================================================ */
function cleanTypingStyle() {
  const sel = window.getSelection();
  if (!sel.rangeCount) return;
  const range = sel.getRangeAt(0);
  let el = (range.startContainer.nodeType === 3)
    ? range.startContainer.parentElement
    : range.startContainer;

  // Recorre los elementos padres y limpia colores heredados
  while (el && el !== editor) {
    if (el.classList && el.classList.contains('mention')) break; // No tocar las menciones
    if (el.style && el.style.color) el.style.color = '';          // Quita estilos de color inline
    el = el.parentElement;
  }

  // Parche visual: fuerza que el texto que se escriba sea negro
  try {
    if (document.queryCommandSupported('foreColor')) {
      document.execCommand('foreColor', false, '#000000');
    }
  } catch (e) { /* No pasa nada si falla */ }

  // Como refuerzo, aseguramos que el editor mantenga color negro
  editor.style.color = 'black';
}


/* ============================================================
   👁️ MUTATION OBSERVER
   Detecta si se borra o cambia algo dentro del editor
   ============================================================ */
const mo = new MutationObserver((mutations) => {
  for (const m of mutations) {
    // Si se elimina una mención del DOM
    if (m.removedNodes && m.removedNodes.length) {
      for (const n of m.removedNodes) {
        if (n.nodeType === 1 && n.classList && n.classList.contains('mention')) {
          // Cuando se borra una mención, limpiar estilo y posición del cursor
          setTimeout(() => {
            cleanTypingStyle();
            const sel = window.getSelection();
            if (sel.rangeCount) {
              const r = sel.getRangeAt(0);
              if (r.collapsed) {
                const neutral = document.createTextNode('\u00A0');
                r.insertNode(neutral);
                placeCaretAfter(neutral);
              }
            }
          }, 0);
        }
      }
    }

    // Si hay mutaciones en atributos de estilo, limpiarlas también
    if (m.type === 'attributes') {
      setTimeout(cleanTypingStyle, 0);
    }
  }
});

// Activamos el observador sobre el editor
mo.observe(editor, { childList: true, subtree: true, attributes: true, attributeFilter: ['style', 'class'] });


/* ============================================================
   📋 FUNCIONES DE SUGERENCIAS (@)
   ============================================================ */

// Muestra la lista de sugerencias debajo del editor
function showSuggestions(list) {
  suggestions.innerHTML = '';
  activeIndex = 0;

  list.forEach((u, i) => {
    const div = document.createElement('div');
    div.textContent = u.name;
    if (i === activeIndex) div.classList.add('active');
    div.addEventListener('mousedown', (ev) => {
      ev.preventDefault();
      restoreCursorPosition();
      insertMention(u.iniciales);  // Inserta la mención seleccionada
    });
    suggestions.appendChild(div);
  });

  // Posiciona el cuadro de sugerencias debajo del editor
  const rect = editor.getBoundingClientRect();
  suggestions.style.left = rect.left + 'px';
  suggestions.style.top = (rect.bottom + window.scrollY) + 'px';
  suggestions.style.display = 'block';
}

// Oculta la lista de sugerencias
function hideSuggestions() { suggestions.style.display = 'none'; activeIndex = -1; }

// Actualiza la clase activa (navegación con flechas)
function updateActive(items) {
  items.forEach(it => it.classList.remove('active'));
  if (activeIndex >= 0 && items[activeIndex]) items[activeIndex].classList.add('active');
}


/* ============================================================
   🧩 FUNCIÓN PARA INSERTAR UNA MENCIÓN
   ============================================================ */
function insertMention(name) {
  const caret = getCaretOffset(editor);
  const plain = editor.textContent || '';
  const before = plain.slice(0, caret);
  const match = before.match(/@([^\s@]*)$/);
  if (!match) { hideSuggestions(); return; }

  const start = caret - match[0].length;
  const end = caret;
  const replRange = rangeFromOffsets(editor, start, end);
  replRange.deleteContents();

  // Crea el span azul (mención)
  const span = document.createElement('span');
  span.className = 'mention';
  span.textContent = name;
  span.setAttribute('contenteditable', 'false');
  span.setAttribute('data-mention', '1');
  replRange.insertNode(span);

  // Inserta un separador invisible (espacio) después con color negro
  const sep = document.createElement('span');
  sep.textContent = '\u00A0';
  sep.style.color = '#000';
  sep.style.fontSize = 'inherit';
  span.after(sep);

  // Mueve el cursor después del separador
  placeCaretAfter(sep);

  hideSuggestions();
  updateMentionsList();

  // Limpia estilo residual de escritura
  setTimeout(cleanTypingStyle, 0);
}


/* ============================================================
   📜 FUNCIÓN PARA ACTUALIZAR LA LISTA DE MENCIONES
   ============================================================ */
function updateMentionsList() {
  mentionsList = [];
  editor.querySelectorAll('span.mention').forEach(span => {
    const t = span.textContent.trim();
    if (t) mentionsList.push(t);
  });
  mentionsList = [...new Set(mentionsList)]; // Evita duplicados
}


/* ============================================================
   🌐 OBTENER USUARIOS DESDE get_users.php (con debounce)
   ============================================================ */

// Función debounce para evitar demasiadas peticiones mientras el usuario escribe
function debounce(fn, wait = 200) {
  let t; return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), wait); };
}

// Llamada fetch para obtener coincidencias desde el servidor
async function fetchUsers(term) {
  const url = 'get_users.php' + (term ? '?q=' + encodeURIComponent(term) : '');
  try {
    const res = await fetch(url);
    return await res.json();
  } catch (e) { console.error(e); return []; }
}

// Ejecuta la búsqueda con retardo controlado (200 ms)
const debouncedFetch = debounce(async (term) => {
  users = await fetchUsers(term);
  filtered = users || [];
  filtered.length ? showSuggestions(filtered) : hideSuggestions();
}, 200);


/* ============================================================
   ⌨️ EVENTOS DEL EDITOR
   ============================================================ */

// Cuando el usuario escribe algo en el editor
editor.addEventListener('keyup', async (e) => {
  // Ignorar teclas de navegación o selección
  if (['ArrowDown','ArrowUp','Enter'].includes(e.key)) return;

  saveCursorPosition();
  const caret = getCaretOffset(editor);
  const plain = editor.textContent || '';
  const before = plain.slice(0, caret);
  const match = before.match(/@([^\s@]*)$/);

 /*  
  
  // Si hay un @ activo, buscar coincidencias
  if (match) {
    const term = match[1];
    debouncedFetch(term);
  } else {
    hideSuggestions();
  }

  */

  if (match) {
  const term = match[1];
  if (term.length >= 2) {
    debouncedFetch(term);   // 🔹 Solo consulta cuando hay 3 o más caracteres
  } else {
    hideSuggestions();      // 🔹 Oculta sugerencias si hay menos de 3 letras
  }
} else {
  hideSuggestions();        // 🔹 Si no hay @, también oculta sugerencias
}

});






// Navegación con teclado dentro de las sugerencias
editor.addEventListener('keydown', (e) => {
  if (suggestions.style.display !== 'block') return;
  if (['ArrowDown','ArrowUp','Enter','Tab'].includes(e.key)) {
    e.preventDefault();
    const items = suggestions.querySelectorAll('div');
    if (!items.length) return;

    if (e.key === 'ArrowDown') { activeIndex = (activeIndex + 1) % items.length; updateActive(items); }
    else if (e.key === 'ArrowUp') { activeIndex = (activeIndex - 1 + items.length) % items.length; updateActive(items); }
    else if (e.key === 'Enter' || e.key === 'Tab') { insertMention(filtered[activeIndex].iniciales); }
  }
});

// Guarda posición del cursor al hacer clic o escribir
editor.addEventListener('mouseup', saveCursorPosition);
editor.addEventListener('keyup', saveCursorPosition);

// Cada vez que el contenido cambia (input)
editor.addEventListener('input', () => {
  updateMentionsList();       // Actualiza menciones
  setTimeout(cleanTypingStyle, 0);  // Limpia colores residuales
});

// Cierra las sugerencias si el usuario hace clic fuera del editor
document.addEventListener('click', (e) => {
  if (!suggestions.contains(e.target) && e.target !== editor) hideSuggestions();
});


/* ============================================================
   💾 BOTÓN GUARDAR: ENVÍO DEL COMENTARIO Y MENCIONES
   ============================================================ */

btnGuardar && btnGuardar.addEventListener('click', async () => {

  const comentario = editor.innerText.trim(); // Texto del comentario sin etiquetas HTML
  const payload = {
    comentario,              // El texto del comentario
    menciones: mentionsList, // Lista de menciones (@JSG, @ABC, etc.)
    usuario: (typeof currentUser !== 'undefined' ? currentUser : null), // Usuario actual (desde PHP)
    // date_time: (new Date()).toISOString() // Fecha y hora actual en formato ISO
    dot_rts: (typeof dot !== 'undefined' ? dot : null), // Dot actual (desde PHP)
    id_rts: (typeof id_rts !== 'undefined' ? id_rts : null), // id_rts actual (desde PHP)
    date_time: formatCurrentDateTime() // Fecha y hora actual en formato DD-MM-AAAA HH:MM:SS

  };

 /*  console.log("datos a enviar", payload);
  return false; */

  // Envío mediante fetch (POST JSON)
  try {
    const res = await fetch('dt_notas_ok_sandbox.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const json = await res.json();
    // const text = await res.text();
    console.log('✅ Guardado correctamente:', json);
    alert('Comentario guardado con éxito.');
    window.location.reload();

  } catch (err) {
    console.error('❌ Error al guardar:', err);
  }
});

/* ============================================================
   FUNCION PARA HORA Y FECHA ACTUAL
   ============================================================ */

const formatCurrentDateTime = () => {

    const now = new Date();

    // Obtener componentes de la fecha
    const day = String(now.getDate()).padStart(2, '0');
    const month = String(now.getMonth() + 1).padStart(2, '0'); // Los meses en JS van de 0 a 11
    const year = now.getFullYear();

    // Obtener componentes de la hora
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');

    // Combinar todo en el formato deseado
    const formattedDateTime = `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;

     return formattedDateTime;

};

</script>
</body>
</html>
