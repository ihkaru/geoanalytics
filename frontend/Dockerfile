# Gunakan base image Node.js 24
FROM node:24-alpine

# Set working directory di dalam container
WORKDIR /app

# Copy package.json dan package-lock.json terlebih dahulu
COPY package*.json ./

# Instal dependencies
RUN npm install

# Copy sisa kode aplikasi
COPY . .

# Expose port dev server (sesuaikan jika F7/Vue Anda beda port)
EXPOSE 8080

# Perintah untuk menjalankan dev server
CMD ["npm", "run", "dev"]