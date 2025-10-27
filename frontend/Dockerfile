# ---------- ⚙️ Base ----------
FROM node:22-alpine AS base

WORKDIR /app
COPY package*.json ./

# Installiere Dependencies
RUN npm install

# ---------- 💻 Development ----------
FROM base AS dev
ENV NODE_ENV=development

# Kopiere Quellcode (für Hot-Reloading per Volume gemountet)
COPY . .

EXPOSE 5173
CMD ["npm", "run", "dev", "--", "--host"]

# ---------- 🚀 Production ----------
FROM base AS prod
ENV NODE_ENV=production

COPY . .

# Baue das Projekt (Vite erzeugt /dist)
RUN npm run build

# Nur statische Dateien behalten
FROM nginx:1.27-alpine AS serve
COPY --from=prod /app/dist /usr/share/nginx/html

# Nginx-Port
EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]
