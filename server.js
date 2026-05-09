require('dotenv').config();
const express = require('express');
const nodemailer = require('nodemailer');
const app = express();

app.use(express.json());
app.use(express.static('public'));

const transporter = nodemailer.createTransport({
  host: 'smtp-relay.brevo.com',
  port: 587,
  auth: {
    user: process.env.BREVO_USER,
    pass: process.env.BREVO_KEY
  }
});

app.post('/api/contact', async (req, res) => {
  const { name, email, message, website } = req.body;
  if (website) return res.json({ ok: true });
  if (!name || !email || !message) return res.status(400).json({ error: 'All fields required' });

  try {
    await transporter.sendMail({
      from: `"${name}" <miguel.diez@zeid10.com>`,
      replyTo: email,
      to: 'miguel.diez@zeid10.com',
      subject: `[Straplio] Contact from ${name}`,
      text: `Name: ${name}\nEmail: ${email}\n\n${message}`
    });
    res.json({ ok: true });
  } catch (err) {
    console.error('Mail error:', err.message);
    res.status(500).json({ error: 'Failed to send' });
  }
});

app.listen(3001, () => console.log('Straplio site running on http://localhost:3001'));
