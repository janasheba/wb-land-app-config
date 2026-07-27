export default function handler(req, res) {
  // Get IP from various headers
  const ip = req.headers['x-forwarded-for']?.split(',')[0].trim() || 
             req.headers['x-real-ip'] || 
             req.socket.remoteAddress ||
             'unknown';
  
  res.status(200).json({
    ip: ip,
    timestamp: new Date().toISOString(),
    userAgent: req.headers['user-agent'] || 'unknown'
  });
}