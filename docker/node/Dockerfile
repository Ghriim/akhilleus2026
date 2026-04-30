FROM node:22-alpine

WORKDIR /app

# CMD installs deps on first run (when the named volume is empty) then starts
# the Vite dev server bound to 0.0.0.0 so the host can reach it.
CMD ["sh", "-c", "if [ ! -f node_modules/.installed ]; then npm install && touch node_modules/.installed; fi && npm run dev -- --host 0.0.0.0"]
