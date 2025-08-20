import Echo from "laravel-echo";

window.Echo = new Echo({
  broadcaster: "reverb",
  key: process.env.MIX_REVERB_APP_KEY,
  wsHost: "api-vendor-service-live.pick-a-part.ca",
  wsPort: 8446,
  wssPort: 8446,
  disableStats: true,
  encrypted: true,
  enabledTransports: ["ws", "wss"],
});
