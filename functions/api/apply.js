/* ==========================================================================
   POST /api/apply  —  the careers application form
   --------------------------------------------------------------------------
   This is the only piece of server-side code on the site. It runs on
   Cloudflare Pages ("Functions"), on civillawfirm.in itself, and it does one
   job: take what an applicant typed on /careers/, and email it to the
   chambers with the curriculum vitae attached.

   Nothing secret is written in this file. The key and the addresses are read
   from environment variables set in the Cloudflare dashboard, so they never
   appear in the website's source and never reach this repository.

   SET THESE THREE, in Cloudflare Pages -> Settings -> Variables and secrets:

     RESEND_API_KEY   the API key from resend.com (mark it as a SECRET)
     CAREERS_TO       who receives applications, separated by commas
     CAREERS_FROM     the From address, e.g.  Civil Law Firm careers
                      <careers@civillawfirm.in> -- the domain must be
                      verified in Resend or nothing will send

   See README.md, section 1c2, for the whole setup.
   ========================================================================== */

const MAX_BYTES = 5 * 1024 * 1024;              /* 5 MB, matching the page */
const ALLOWED_EXTENSIONS = ['.pdf', '.doc', '.docx'];
const MAX_LENGTHS = { name: 80, position: 40, email: 120, phone: 20, message: 1200 };

export async function onRequestPost(context) {
  const { request, env } = context;
  const wantsJson = (request.headers.get('accept') || '').includes('application/json');

  try {
    if (!env.RESEND_API_KEY || !env.CAREERS_TO || !env.CAREERS_FROM) {
      /* Deliberately explicit: this is a setup fault, not the applicant's. */
      return reply(wantsJson, 503,
        'The application form is not switched on yet. Please email the chambers instead.');
    }

    const form = await request.formData();

    /* Spam trap. A real applicant never sees this field, so anything in it is
       a robot. Answer as though it worked, and drop it. */
    if (String(form.get('botcheck') || '').length > 0) {
      return reply(wantsJson, 200, 'Thank you — your application has been sent.');
    }

    /* Angle brackets are stripped so nothing tag-like is ever emailed on. */
    const field = (key) => String(form.get(key) || '').replace(/[<>]/g, '').trim();

    const name = field('name');
    const position = field('position');
    const email = field('email');
    const phone = field('phone');
    const message = field('message');

    if (!name || !email || !phone || !message) {
      return reply(wantsJson, 400, 'Please fill in every field.');
    }
    for (const key of Object.keys(MAX_LENGTHS)) {
      if (field(key).length > MAX_LENGTHS[key]) {
        return reply(wantsJson, 400, 'One of the answers is longer than the form allows.');
      }
    }
    if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email)) {
      return reply(wantsJson, 400, 'Please check the email address.');
    }
    if (phone.replace(/\D/g, '').length < 7) {
      return reply(wantsJson, 400, 'Please give a telephone number the chambers can call back on.');
    }

    const file = form.get('attachment');
    if (!file || typeof file.arrayBuffer !== 'function' || !file.size) {
      return reply(wantsJson, 400, 'Please attach a curriculum vitae.');
    }
    const filename = String(file.name || 'cv');
    if (!ALLOWED_EXTENSIONS.some((ext) => filename.toLowerCase().endsWith(ext))) {
      return reply(wantsJson, 400, 'The curriculum vitae must be a PDF or a Word document.');
    }
    if (file.size > MAX_BYTES) {
      return reply(wantsJson, 400, 'The curriculum vitae must be 5 MB or smaller.');
    }

    const body = [
      'An application has come in through civillawfirm.in.',
      '',
      'Applying for:  ' + (position || 'not stated'),
      'Name:          ' + name,
      'Email:         ' + email,
      'Telephone:     ' + phone,
      '',
      'About the applicant:',
      message,
      '',
      'The curriculum vitae is attached. Replying to this email answers the',
      'applicant directly.'
    ].join('\n');

    const sent = await fetch('https://api.resend.com/emails', {
      method: 'POST',
      headers: {
        'Authorization': 'Bearer ' + env.RESEND_API_KEY,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        from: env.CAREERS_FROM,
        to: env.CAREERS_TO.split(',').map((s) => s.trim()).filter(Boolean),
        reply_to: email,
        subject: 'Careers application — ' + name + (position ? ' (' + position + ')' : ''),
        text: body,
        attachments: [{ filename: filename, content: await toBase64(file) }]
      })
    });

    if (!sent.ok) {
      return reply(wantsJson, 502,
        'The application could not be sent. Please email the chambers instead.');
    }

    return reply(wantsJson, 200, 'Thank you — your application has been sent.');
  } catch (err) {
    return reply(wantsJson, 500,
      'The application could not be sent. Please email the chambers instead.');
  }
}

/* Opening /api/apply in a browser should say so plainly rather than 404. */
export async function onRequestGet() {
  return new Response('This address only accepts the careers form.', {
    status: 405,
    headers: { 'Content-Type': 'text/plain; charset=utf-8', 'Allow': 'POST' }
  });
}

/* Base64, in fixed chunks. Spreading a whole file into String.fromCharCode
   overflows the call stack on anything larger than a very small one. */
async function toBase64(file) {
  const bytes = new Uint8Array(await file.arrayBuffer());
  const CHUNK = 0x8000;
  let binary = '';
  for (let i = 0; i < bytes.length; i += CHUNK) {
    binary += String.fromCharCode.apply(null, bytes.subarray(i, i + CHUNK));
  }
  return btoa(binary);
}

function reply(wantsJson, status, message) {
  const success = status === 200;

  if (wantsJson) {
    return new Response(JSON.stringify({ success: success, message: message }), {
      status: status,
      headers: { 'Content-Type': 'application/json; charset=utf-8' }
    });
  }

  /* With JavaScript switched off the form posts normally, so the answer has
     to be a readable page rather than raw JSON. It borrows the site's own
     stylesheet. This fallback is in English only. */
  const heading = success ? 'Application sent' : 'Application not sent';
  const page =
    '<!DOCTYPE html><html lang="en-IN"><head><meta charset="utf-8">' +
    '<meta name="viewport" content="width=device-width, initial-scale=1">' +
    '<meta name="robots" content="noindex">' +
    '<title>' + heading + ' | Civil Law Firm</title>' +
    '<link rel="stylesheet" href="/css/styles.css?v=3"></head><body>' +
    '<main id="main"><div class="wrap"><header class="page-head">' +
    '<h1>' + heading + '</h1><p class="lede">' + escapeHtml(message) + '</p>' +
    '</header><p class="cta-row">' +
    '<a class="btn btn-outline" href="/careers/">Back to Careers</a>' +
    '<a class="btn btn-ghost" href="/contact/">Contact the chambers</a>' +
    '</p></div></main></body></html>';

  return new Response(page, {
    status: status,
    headers: { 'Content-Type': 'text/html; charset=utf-8' }
  });
}

function escapeHtml(value) {
  return String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;');
}
