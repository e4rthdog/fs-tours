# Configuration Setup

## Initial Setup

After cloning the repository, you need to set up your configuration file:

### 1. Copy the configuration template

```bash
cd frontend/src/config
cp config.example.js config.js
```

### 2. Edit the configuration file

Open `frontend/src/config/config.js` and update the settings:

```javascript
export const config = {
	// Update this to your API server URL
	apiBaseUrl: 'https://your-api-domain.com',

	// Update this to your SimBrief username
	simbriefUsername: 'your-simbrief-username',
}
```

### 3. Configuration Options

#### `apiBaseUrl`

- The base URL for your FS Tours API server
- Examples:
  - Development: `http://fs-tours-api.ddev.site`
  - Production: `https://api.your-domain.com`

#### `simbriefUsername`

- Your SimBrief account username
- Used to fetch flight plans from SimBrief
- Must be a valid SimBrief username

## Security Notes

- The `config.js` file is ignored by git and will not be committed
- Never commit sensitive configuration data to the repository
- Each deployment environment should have its own `config.js` file
- The `config.example.js` file serves as a template and should be kept updated

## Deployment

For production deployment:

1. Copy `config.example.js` to `config.js`
2. Update with production settings
3. Ensure your web server serves the frontend application
4. The configuration will be bundled into the application at build time
