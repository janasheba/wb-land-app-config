export const metadata = {
  title: 'IP Tracker',
  description: 'Track your public IP address',
};

export default function RootLayout({ children }) {
  return (
    <html lang="en">
      <body>{children}</body>
    </html>
  );
}