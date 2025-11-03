# ---------- ⚙️ Base ----------
FROM node:22-alpine AS base

WORKDIR /app
COPY frontend/package*.json ./

# 🔒 Sicheres & reproduzierbares Installieren (mit lockfile)
RUN npm ci || npm install

# 📦 Installiere zusätzlich @vueuse/core
RUN npm install @vueuse/core

# ---------- 💻 Development ----------
FROM base AS dev
ENV NODE_ENV=development

# 📁 Kopiere Code (wird zusätzlich als Volume gemountet)
COPY frontend/ ./

# 🚀 Expose Vite Dev Server
EXPOSE 5173

# Startet Vite im Host-Modus (damit localhost in Docker funktioniert)
CMD ["npm", "run", "dev", "--", "--host"]

# ---------- 🚀 Production ----------
FROM base AS prod
ENV NODE_ENV=production

# 📁 Projektdateien kopieren und bauen
COPY frontend/ ./
RUN npm run build

# ---------- 🌐 Nginx Serve Stage ----------
FROM nginx:1.27-alpine AS serve

# 🧩 Kopiere nur das gebaute Frontend
COPY --from=prod /app/dist /usr/share/nginx/html

# 🔧 Optional: Custom Nginx Config für SPA (vue-router support)
# Falls du vue-router im "history"-Modus nutzt:
COPY ./nginx/default.conf /etc/nginx/conf.d/default.conf

EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]
