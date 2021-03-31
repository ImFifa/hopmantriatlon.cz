/* eslint-env node */
// Node
const path = require("path");

// Webpack
const webpack = require("webpack");
const merge = require("webpack-merge");

// Webpack plugins
const TerserPlugin = require("terser-webpack-plugin");
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const OptimizeCSSAssetsPlugin = require("optimize-css-assets-webpack-plugin");
const CopyWebpackPlugin = require("copy-webpack-plugin");
const CompressionPlugin = require("compression-webpack-plugin");

// Other
const devMode = process.env.NODE_ENV !== "production";

// Webpack abilities
const WEBPACK_DEV_SERVER_HOST = process.env.WEBPACK_DEV_SERVER_HOST || "localhost";
const WEBPACK_DEV_SERVER_PORT = parseInt(process.env.WEBPACK_DEV_SERVER_PORT, 10) || 8080;

// Config
const ROOT_PATH = __dirname;
const CACHE_PATH = ROOT_PATH + "/temp/webpack";

module.exports = {
	mode: devMode ? "development" : "production",
	context: path.join(ROOT_PATH, "app/assets"),
	entry: {
		admin: path.join(ROOT_PATH, "vendor/simple-cms/core-module/assets/src/admin.js"),
		bridge: path.join(ROOT_PATH, "vendor/simple-cms/core-module/assets/src/bridge.js"),
		front: path.join(ROOT_PATH, "app/assets/src/front.js"),
	},
	output: {
		path: path.join(ROOT_PATH, "www/dist"),
		publicPath: "/dist/",
		filename: "[name].bundle.js",
		chunkFilename: "[name].chunk.js",
		globalObject: "this", // allow HMR and web workers to play nice
	},
	module: {
		rules: [
			{
				test: /\.js$/,
				exclude: path => /node_modules/.test(path),
				loader: "babel-loader",
				options: {
					cacheDirectory: path.join(CACHE_PATH, "babel-loader"),
				},
			},
			{
				test: /\.(eot|svg|ttf|woff(2)?)(\?v=\d+\.\d+\.\d+)?/,
				loader: "file-loader",
				options: {
					name: "fonts/[name].[hash:8].[ext]",
				},
			},
			{
				test: /\.(png|jpg|gif|ico)$/,
				loader: "file-loader",
				options: {
					name: "img/[name].[ext]",
				}
			},
			{
				test: /\.css$/,
				use: [
					MiniCssExtractPlugin.loader,
					"css-loader",
					{ loader: "postcss-loader", options: { ident: "postcss", plugins: [ require("autoprefixer") ] } }
				],
			},
			{
				test: /\.less$/,
				use: [
					MiniCssExtractPlugin.loader,
					"css-loader",
					{ loader: "postcss-loader", options: { ident: "postcss", plugins: [ require("autoprefixer") ] } },
					"less-loader"
				],
			},
			{
				test: /\.(scss|sass)$/,
				use: [
					MiniCssExtractPlugin.loader,
					"css-loader",
					{ loader: "postcss-loader", options: { ident: "postcss", plugins: [ require("autoprefixer") ] } },
					"sass-loader"
				],
			},
		]
	},
	resolve: {
		alias: {
			"@theme": path.resolve(__dirname, "app/assets/theme"),
			"@": path.resolve(__dirname, "app/assets/src"),
		},
		extensions: [".js"]
	},
	plugins: [
		// fix legacy jQuery plugins which depend on globals
		new webpack.ProvidePlugin({
			$: "jquery",
			jQuery: "jquery",
			"window.jQuery": "jquery",
			"window.$": "jquery",
			Popper: ["popper.js", "default"],
		}),

		// prevent pikaday from including moment.js
		new webpack.IgnorePlugin(/moment/, /pikaday/),

		// ignore locales from moment.js
		new webpack.IgnorePlugin(/^\.\/locale$/, /moment$/),

		// extract css
		new MiniCssExtractPlugin({
			filename: !devMode ? "[name].bundle.css" : "[name].bundle.css",
		}),

		new CopyWebpackPlugin([
			{ from: path.join(ROOT_PATH, "app/assets/favicon"), to: "./favicon" },
			{ from: path.join(ROOT_PATH, "app/assets/img"), to: "./img", ignore: ["*.psd", "*.ai"] }
		])
	],
	devtool: "source-map",
	performance: {
		hints: false
	},
	node: {fs: "empty"},
};

if (process.env.NODE_ENV === "development") {
	const development = {
		devServer: {
			host: WEBPACK_DEV_SERVER_HOST,
			port: WEBPACK_DEV_SERVER_PORT,
			disableHostCheck: true,
			contentBase: path.join(ROOT_PATH, "www"),
			headers: {
				"Access-Control-Allow-Origin": "*",
				"Access-Control-Allow-Headers": "*",
			},
			stats: "errors-only",
			hot: true,
			inline: true
		},
	};

	module.exports = merge(module.exports, development);
}

if (process.env.NODE_ENV === "production") {
	const production = {
		devtool: "source-map",
		optimization: {
			minimizer: [
				new TerserPlugin({
					terserOptions: {
						cache: `${CACHE_PATH}/webpack/terser`,
						parallel: true,
						ecma: 5,
						warnings: false,
						parse: {},
						compress: {},
						mangle: true, // Note `mangle.properties` is `false` by default.
						module: false,
						output: null,
						toplevel: false,
						nameCache: null,
						ie8: false,
						keep_classnames: undefined,
						keep_fnames: false,
						safari10: true
					}
				})
			]
		},
		plugins: [
			// optimize CSS files
			new OptimizeCSSAssetsPlugin(),

			// compression can require a lot of compute time
			new CompressionPlugin()
		],
	};

	module.exports = merge(module.exports, production);
}
