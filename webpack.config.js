const path = require('path');

module.exports = {
	entry: './src/admin.js',
	output: { path: path.resolve(__dirname, 'dist') },
	module: {
		rules: [
			{ test: /\.css$/, use: ['style-loader', 'css-loader'] }
		]
	}
 };