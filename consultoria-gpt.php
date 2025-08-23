<?php
/*
Plugin Name: Consultoria GPT
Description: Asistente IA (estilo ChatGPT) para consultoriainformatica.net. Shortcode: [consultoria_gpt]
Version: 1.6
Author: Amadeo
*/

if (!defined('ABSPATH')) exit;

/* =========================
 *  ADMIN MENU & SETTINGS
 * ========================= */
add_action('admin_menu', function() {
    add_menu_page('Consultoria GPT', 'Consultoria GPT', 'manage_options', 'consultoria-gpt', 'ci_gpt_settings_page', 'dashicons-format-chat');
    add_submenu_page('consultoria-gpt', 'Ajustes', 'Ajustes', 'manage_options', 'consultoria-gpt', 'ci_gpt_settings_page');
    add_submenu_page('consultoria-gpt', 'Shortcode', 'Shortcode', 'manage_options', 'consultoria-gpt-shortcode', 'ci_gpt_shortcode_page');
});

add_action('admin_init', function() {
    register_setting('ci_gpt_options', 'ci_gpt_api_key');
    register_setting('ci_gpt_options', 'ci_gpt_logo');
    register_setting('ci_gpt_options', 'ci_gpt_model');
    register_setting('ci_gpt_options', 'ci_gpt_theme'); // light | dark | auto
});

function ci_gpt_settings_page() {
    $api   = esc_attr(get_option('ci_gpt_api_key'));
    $logo  = esc_attr(get_option('ci_gpt_logo'));
    $model = esc_attr(get_option('ci_gpt_model', 'gpt-4o-mini'));
    $theme = esc_attr(get_option('ci_gpt_theme', 'light')); ?>
    <div class="wrap">
        <h1>Consultoria GPT — Ajustes</h1>
        <form method="post" action="options.php">
            <?php settings_fields('ci_gpt_options'); do_settings_sections('ci_gpt_options'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">API Key OpenAI</th>
                    <td><input type="password" name="ci_gpt_api_key" value="<?php echo $api; ?>" style="width:420px;" placeholder="sk-..."></td>
                </tr>
                <tr>
                    <th scope="row">Logo (URL)</th>
                    <td><input type="text" name="ci_gpt_logo" value="<?php echo $logo; ?>" style="width:420px;" placeholder="https://.../logo.png"></td>
                </tr>
                <tr>
                    <th scope="row">Modelo</th>
                    <td>
                        <input type="text" name="ci_gpt_model" value="<?php echo $model; ?>" style="width:420px;" placeholder="gpt-4o-mini">
                        <p class="description">Modelo de la API de OpenAI (ej.: <code>gpt-4o-mini</code>, <code>gpt-4.1-mini</code>). Debe existir en la API.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Tema visual</th>
                    <td>
                        <select name="ci_gpt_theme">
                            <?php
                            $opts = ['light'=>'Claro (forzado)','dark'=>'Oscuro (forzado)','auto'=>'Automático (según el sistema)'];
                            $current = $theme ?: 'light';
                            foreach($opts as $val=>$label){
                                echo '<option value="'.esc_attr($val).'" '.selected($current,$val,false).'>'.esc_html($label).'</option>';
                            }
                            ?>
                        </select>
                        <p class="description">Si tienes problemas en móvil con fondos oscuros, deja <strong>Claro (forzado)</strong>.</p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
<?php }

function ci_gpt_shortcode_page() { ?>
    <div class="wrap">
        <h1>Shortcode</h1>
        <p>Inserta este shortcode en cualquier página o entrada donde quieras mostrar el chat:</p>
        <pre style="font-size:16px;padding:12px;background:#fff;border:1px solid #ccc;border-radius:6px;">[consultoria_gpt]</pre>
        <p>Recomendación: crea una página “Agente IA” y pega el shortcode en el bloque “Código corto”.</p>
    </div>
<?php }

/* =========================
 *  FRONTEND (SHORTCODE) — Shadow DOM aislado
 * ========================= */
add_shortcode('consultoria_gpt', function() {
    ob_start();
    $logo  = esc_attr(get_option('ci_gpt_logo'));
    $ajax  = esc_js(admin_url('admin-ajax.php?action=ci_gpt_chat'));
    $theme = esc_attr(get_option('ci_gpt_theme','light')); ?>
<div id="ci-gpt-mount"
     data-logo="<?php echo $logo; ?>"
     data-ajax="<?php echo $ajax; ?>"
     data-theme="<?php echo $theme ? $theme : 'light'; ?>"
     style="display:block;contain:content;position:relative;z-index:1;"></div>

<script>
(function(){
  const mount = document.getElementById('ci-gpt-mount');
  if (!mount) return;
  const ajaxUrl  = mount.getAttribute('data-ajax');
  const logoUrl  = mount.getAttribute('data-logo') || '';
  const themeOpt = (mount.getAttribute('data-theme') || 'light').toLowerCase();

  const overlay = document.createElement('div');
  overlay.style.cssText = 'position:fixed;inset:0;z-index:999999;background:' + (themeOpt==='dark' ? '#0b0f14' : '#fff') + ';display:flex;justify-content:center;align-items:stretch;';
  document.body.innerHTML = '';
  document.documentElement.style.height = '100%';
  document.body.style.height = '100%';
  document.body.style.margin = '0';
  document.body.appendChild(overlay);

  const host = document.createElement('div');
  host.style.cssText = 'width:100%;max-width:1400px;height:100%;';
  overlay.appendChild(host);
  const root = host.attachShadow({mode:'open'});

  const metaViewport = document.querySelector('meta[name="viewport"]');
  if (metaViewport) {
    metaViewport.setAttribute('content','width=device-width,initial-scale=1,maximum-scale=1');
  }

  const css = `
  :host{ all: initial; color-scheme: light; } /* forzar controles claros por defecto */
  *,*::before,*::after{ box-sizing: border-box; }
  :host{ font-family: system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,'Helvetica Neue',Arial,'Noto Sans',sans-serif; color:#0f172a; }
  :root{
    --bd:#e5e7eb; --mut:#f8fafc; --mut2:#fcfcfd; --pri:#0b63d1;
    --ai:#f7f8fa; --ai-b:#e6e7ea; --us:#dff2ff; --us-b:#c7e6ff;
    --chip:#ffffff; --chip-b:#d1d5db;
  }
  .wrap{ position:absolute; inset:0; display:flex; flex-direction:column; width:100%; height:100%; margin:0; border:none; border-radius:0; overflow:hidden; background:#fff; box-shadow:none; }
  .header{ text-align:center; padding:22px 18px; background:var(--mut); border-bottom:1px solid var(--bd); }
  .header img{ max-height:56px; margin:0 auto 8px; display:block; }
  .title{ margin:4px 0 2px; font-size: clamp(18px,2.2vw,22px); font-weight:800; }
  .desc{ margin:0; font-size: clamp(12px,1.6vw,14px); color:#4b5563; }
  .chips{ display:flex; gap:8px; flex-wrap:wrap; justify-content:center; padding:12px; background:var(--mut2); border-bottom:1px solid #eef2f7; overflow-x:auto; scroll-snap-type:x mandatory; }
  .chip{ scroll-snap-align:start; padding:9px 12px; border-radius:999px; border:1px solid var(--chip-b); background:var(--chip); cursor:pointer; font-size:clamp(12px,1.8vw,14px); color:#0f172a; white-space:nowrap; box-shadow:0 2px 0 rgba(0,0,0,.02); transition: background .15s,border-color .15s,transform .08s }
  .chip:hover{ background:#eef2ff; border-color:#c7d2fe; }
  .chip:active{ transform: translateY(1px); }
  .chip[disabled]{ opacity:.5; cursor:not-allowed; }
  .msgs{ flex:1; overflow-y:auto; padding:14px 16px; background:#fff; }
  .row{ display:flex; margin:6px 0; }
  .row.user{ justify-content:flex-end; }
  .bubble{ max-width:88%; padding:10px 12px; border-radius:16px; line-height:1.55; white-space:pre-wrap; word-wrap:break-word; font-size:clamp(13px,1.8vw,15px); }
  .row.user .bubble{ background:var(--us); border:1px solid var(--us-b); }
  .row.ai .bubble{ background:var(--ai); border:1px solid var(--ai-b); }
  .input{ display:flex; gap:8px; padding:10px 12px; border-top:1px solid var(--bd); background:#ffffff; position:sticky; bottom:0; left:0; right:0; }
  .field{ flex:1; padding:12px 14px; border:1px solid #d1d5db; border-radius:12px; font-size:16px; outline:none; background:#fff; color:#0f172a; }
  .field::placeholder{ color:#9aa3ae; }
  .field:focus{ border-color:#93c5fd; box-shadow:0 0 0 3px rgba(59,130,246,.15); }
  .send{ width:46px; min-width:46px; height:46px; display:flex; align-items:center; justify-content:center; border:none; border-radius:12px;
         background:var(--pri); color:#fff; cursor:pointer; box-shadow: 0 1px 0 rgba(0,0,0,.12), inset 0 0 0 1px rgba(255,255,255,.2); }
  .send:hover{ filter: brightness(1.08); }
  .send[disabled]{ opacity:.6; cursor:not-allowed; }
  .send svg{ width:22px; height:22px; display:block; fill:currentColor; filter: drop-shadow(0 1px 0 rgba(0,0,0,.45)); } /* visible siempre */
  .send svg path{ stroke: rgba(0,0,0,.55); stroke-width: .6px; }
  .contact-ctas{ display:flex; flex-wrap:wrap; gap:8px; margin-top:12px; }
  .cta{ flex:1; padding:8px 12px; border-radius:8px; text-align:center; background:var(--pri); color:#fff; text-decoration:none; font-size:clamp(12px,1.8vw,14px); }
  .cta:hover{ filter: brightness(1.08); }
  .typing{ display:inline-flex; align-items:center; gap:4px; }
  .dot{ width:6px; height:6px; border-radius:50%; background:#606770; opacity:.4; animation:blink 1.2s infinite; }
  .dot:nth-child(2){ animation-delay:.2s; } .dot:nth-child(3){ animation-delay:.4s; }
  @keyframes blink{ 0%,80%,100%{opacity:.2} 40%{opacity:1} }
  @media (max-width:560px){
    .chips{ justify-content:flex-start; padding:10px 8px; }
  }
  .input{ padding-bottom: calc(12px + env(safe-area-inset-bottom)); }
  `;

  // Dark theme overrides only if themeOpt == 'dark' OR (themeOpt=='auto' && prefers dark)
  const darkCSS = `
  :host{ color-scheme: dark; }
  :root{
    --bd:#2b2f36; --mut:#101318; --mut2:#0c0f14; --ai:#141922; --ai-b:#1f2430;
    --us:#0f2540; --us-b:#15365c; --chip:#0f1420; --chip-b:#2c3444;
  }
  :host{ color:#e5e7eb; }
  .wrap{ background:#0b0f14; box-shadow:none; }
  .desc{ color:#b3b8c2; }
  .chip{ color:#e5e7eb; }
  .field{ background:#0e131a; color:#e6edf5; border-color:#293241; }
  .field::placeholder{ color:#8b93a1; }
  .input{ background:#0b0f14; }
  .send{ background:var(--pri); color:#fff; }
  `;

  // Build base HTML
  const html = `
    <div class="wrap">
      <div class="header">
        ${logoUrl ? `<img src="${logoUrl}" alt="Consultoría Informática">` : ''}
        <div class="title">Consultoría Informática</div>
        <p class="desc">Asistente especializado en automatización, desarrollo web, inteligencia artificial y soluciones digitales para empresas.</p>
      </div>
      <div class="chips" id="chips">
        <button class="chip" data-q="¿Cómo puede ayudarme la inteligencia artificial en mi negocio?">¿Cómo puede ayudarme la IA en mi negocio?</button>
        <button class="chip" data-q="Quiero mejorar mi página web, ¿por dónde empiezo?">¿Por dónde empiezo con mi web?</button>
        <button class="chip" data-q="¿Qué es la automatización de procesos y cómo puedo aplicarla?">Automatización de procesos</button>
        <button class="chip" data-q="¿Me podéis hacer una auditoría SEO?">¿Podéis hacer una auditoría SEO?</button>
      </div>
      <div class="msgs" id="msgs"></div>
      <div class="input">
        <input class="field" id="field" type="text" placeholder="Escribe tu mensaje… (Enter para enviar)" autocomplete="off">
        <button class="send" id="send" aria-label="Enviar" title="Enviar">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 11.1c-.9-.4-.9-1.7 0-2.1L20.6 1.8c.9-.4 1.8.5 1.4 1.4l-7.2 18.1c-.3.8-1.5.7-1.8-.1l-2.2-5.4c-.1-.3-.4-.5-.7-.6l-7.6-3.1zM9.2 12.5l3.3 8.1 6.1-15.5-9.4 3.8 3.6 1.5c.5.2.6.9.2 1.2l-3.8 2.9z"></path></svg>
          <span style="position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden">Enviar</span>
        </button>
      </div>
    </div>
  `;

  // Mount base CSS + optional dark
  root.innerHTML = `<style>${css}</style>${html}`;
  if (themeOpt === 'dark') {
    root.innerHTML = `<style>${css}${darkCSS}</style>${html}`;
  } else if (themeOpt === 'auto') {
    const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    root.innerHTML = `<style>${css}${prefersDark ? darkCSS : ''}</style>${html}`;
  }

  // JS logic isolated
  const msgsEl = root.getElementById('msgs');
  const fieldEl = root.getElementById('field');
  const sendBtn = root.getElementById('send');
  const chips = root.getElementById('chips');
  let sending = false;

  // History
  let history = [];
  try { const saved = sessionStorage.getItem('ciMessages'); if(saved) history = JSON.parse(saved); } catch(e){}
  if (history.length) { history.forEach(m => render(m.role, m.content)); scroll(); }

  function persist(){ try{ sessionStorage.setItem('ciMessages', JSON.stringify(history)); } catch(e){} }
  function scroll(){ msgsEl.scrollTop = msgsEl.scrollHeight; }
  function setSending(state){ sending = state; sendBtn.disabled = state; Array.from(chips.children).forEach(b=>b.disabled=state); }
  function typingOn(){ render('ai','',true); scroll(); }
  function typingOff(){ Array.from(msgsEl.querySelectorAll('[data-typing="1"]')).forEach(n=>n.remove()); }

    function render(role, text, typing=false){
      const row = document.createElement('div');
      row.className = 'row ' + (role==='user'?'user':'ai');
      const bubble = document.createElement('div');
      bubble.className = 'bubble';
      if (typing){
        row.dataset.typing = '1';
        const t = document.createElement('div');
        t.className = 'typing';
        t.innerHTML = '<span class="dot"></span><span class="dot"></span><span class="dot"></span>';
        bubble.appendChild(t);
      } else {
        const txt = document.createElement('div');
        txt.textContent = text;
        bubble.appendChild(txt);
        if(role !== 'user'){
          const ctas = document.createElement('div');
          ctas.className = 'contact-ctas';
          ctas.innerHTML = '<a class="cta" href="tel:643932121">Llámanos ahora</a>'+
            '<a class="cta" href="https://api.whatsapp.com/send?phone=+34643932121&text=Me%20gustar%C3%ADa%20recibir%20m%C3%A1s%20informaci%C3%B3n!" target="_blank" rel="noopener">Háblanos por WhatsApp</a>'+
            '<a class="cta" href="mailto:info@consultoriainformatica.net">Escríbenos</a>';
          bubble.appendChild(ctas);
        }
      }
      row.appendChild(bubble);
      msgsEl.appendChild(row);
    }

  async function send(txt){
    if(!txt || sending) return;
    setSending(true);
    history.push({role:'user',content:txt});
    render('user', txt);
    fieldEl.value='';
    typingOn();
    try{
      const res = await fetch(ajaxUrl, {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        credentials:'same-origin',
        body: JSON.stringify({messages: history})
      });
      const data = await res.json();
      typingOff();
      const reply = (data && data.reply) ? data.reply : (data && data.error ? data.error : 'No se pudo obtener respuesta.');
      history.push({role:'assistant',content:reply});
      render('ai', reply);
    }catch(err){
      typingOff();
      const msg = 'Error de conexión. Inténtalo de nuevo.';
      history.push({role:'assistant',content:msg});
      render('ai', msg);
      console.error(err);
    }finally{
      persist(); scroll(); setSending(false);
    }
  }

  sendBtn.addEventListener('click', ()=> send(fieldEl.value.trim()));
  fieldEl.addEventListener('keydown', (e)=>{ if(e.key==='Enter' && !e.shiftKey){ e.preventDefault(); send(fieldEl.value.trim()); } });
  chips.addEventListener('click', (e)=>{
    const b = e.target.closest('.chip'); if(!b) return;
    const q = b.getAttribute('data-q'); if(q) send(q);
  });

  // Ajuste de altura ya manejado con flexbox
})();
</script>
<?php
    return ob_get_clean();
});

/* =========================
 *  AJAX: SERVER SIDE
 * ========================= */
add_action('wp_ajax_ci_gpt_chat', 'ci_gpt_chat');
add_action('wp_ajax_nopriv_ci_gpt_chat', 'ci_gpt_chat');
add_action('wp_ajax_ci_gpt_google_login', 'ci_gpt_google_login');
add_action('wp_ajax_nopriv_ci_gpt_google_login', 'ci_gpt_google_login');

function ci_gpt_chat() {
    header('Content-Type: application/json; charset=utf-8');

    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true);
    $messages = isset($payload['messages']) && is_array($payload['messages']) ? $payload['messages'] : [];

    $api_key = trim((string) get_option('ci_gpt_api_key'));
    $model   = trim((string) get_option('ci_gpt_model', 'gpt-4o-mini'));
    if (!$api_key) {
        echo json_encode(['reply'=>null,'error'=>'Falta configurar la API Key en Ajustes > Consultoria GPT.']);
        wp_die();
    }
    if (!$model) $model = 'gpt-4o-mini';

    if (count($messages) > 16) { $messages = array_slice($messages, -16); }

    foreach ($messages as &$m) {
        if (!isset($m['role']) || !isset($m['content'])) continue;
        $m['role'] = ($m['role']==='assistant'?'assistant':($m['role']==='system'?'system':'user'));
        $m['content'] = wp_strip_all_tags((string) $m['content']);
    } unset($m);

    $system_prompt = "Eres “Consultoría Informática”, un asistente especializado que representa a la empresa consultoriainformatica.net. "
        . "Tu función es asesorar a los usuarios sobre los servicios que ofrece la empresa, como: desarrollo web en WordPress, inteligencia artificial, automatización de procesos, SEO, formación en IA y consultoría tecnológica para pymes. "
        . "Solo puedes utilizar información disponible en la web oficial: https://consultoriainformatica.net/. No inventes servicios ni detalles. "
        . "Si no estás seguro de algo, invita al usuario a consultar directamente con el equipo o visitar la web. "
        . "Tu tono es profesional, claro y directo. Ayuda al usuario con lenguaje humano, sin tecnicismos innecesarios. "
        . "Datos de contacto: WhatsApp 643 93 21 21, Email info@consultoriainformatica.net, Web https://consultoriainformatica.net/.";

    array_unshift($messages, ['role'=>'system','content'=>$system_prompt]);

    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json'
        ],
        'timeout' => 30,
        'body' => wp_json_encode([
            'model' => $model,
            'messages' => $messages,
            'temperature' => 0.7,
            'max_tokens' => 700
        ])
    ]);

    if (is_wp_error($response)) {
        echo json_encode(['reply'=>null, 'error'=>'Error de conexión con OpenAI: ' . $response->get_error_message()]);
        wp_die();
    }

    $code = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);

    if ($code !== 200) {
        $msg = isset($body['error']['message']) ? $body['error']['message'] : ('Código HTTP ' . $code);
        echo json_encode(['reply'=>null, 'error'=>'OpenAI: ' . $msg]);
        wp_die();
    }

    $reply = isset($body['choices'][0]['message']['content']) ? $body['choices'][0]['message']['content'] : null;
    if (!$reply) {
        echo json_encode(['reply'=>null, 'error'=>'Respuesta vacía de OpenAI.']);
        wp_die();
    }

    echo json_encode(['reply'=>$reply]);
    wp_die();
}

function ci_gpt_google_login() {
    header('Content-Type: application/json; charset=utf-8');

    $token = isset($_POST['id_token']) ? sanitize_text_field($_POST['id_token']) : '';
    if (!$token) {
        echo json_encode(['success'=>false,'error'=>'Token faltante']);
        wp_die();
    }

    $verify = wp_remote_get('https://oauth2.googleapis.com/tokeninfo?id_token=' . rawurlencode($token));
    if (is_wp_error($verify)) {
        echo json_encode(['success'=>false,'error'=>'Error de conexión con Google']);
        wp_die();
    }

    $code = wp_remote_retrieve_response_code($verify);
    $body = json_decode(wp_remote_retrieve_body($verify), true);
    if ($code !== 200 || !is_array($body) || empty($body['email'])) {
        echo json_encode(['success'=>false,'error'=>'Token inválido']);
        wp_die();
    }

    $email = sanitize_email($body['email']);
    $name  = sanitize_text_field($body['name'] ?? '');
    $first = sanitize_text_field($body['given_name'] ?? '');
    $last  = sanitize_text_field($body['family_name'] ?? '');

    $user = get_user_by('email', $email);
    $pass = wp_generate_password(20, true, true);

    if ($user) {
        $user_id = wp_insert_user([
            'ID' => $user->ID,
            'user_pass' => $pass,
            'display_name' => $name,
            'first_name' => $first,
            'last_name' => $last,
        ]);
    } else {
        $login = sanitize_user(current(explode('@', $email)), true);
        if (username_exists($login)) {
            $login .= '_' . wp_generate_password(4, false, false);
        }
        $user_id = wp_insert_user([
            'user_login' => $login,
            'user_email' => $email,
            'user_pass' => $pass,
            'display_name' => $name,
            'first_name' => $first,
            'last_name' => $last,
        ]);
    }

    if (is_wp_error($user_id)) {
        echo json_encode(['success'=>false,'error'=>$user_id->get_error_message()]);
        wp_die();
    }

    $creds = [
        'user_login' => get_userdata($user_id)->user_login,
        'user_password' => $pass,
        'remember' => true,
    ];
    $signon = wp_signon($creds, false);
    if (is_wp_error($signon)) {
        echo json_encode(['success'=>false,'error'=>$signon->get_error_message()]);
        wp_die();
    }

    echo json_encode(['success'=>true,'user'=>[
        'id' => $user_id,
        'email' => $email,
        'name' => $name,
    ]]);
    wp_die();
}

?>