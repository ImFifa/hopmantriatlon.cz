module.exports = {
	"env": {
		"browser": true,
		"es6": true,
		"jquery": true
	},
	"extends": [
		"eslint:recommended"
	],
	"parserOptions": {
		"sourceType": "module"
	},
	"rules": {
		"no-console": [
			0
		],
		"indent": [
			"error",
			"tab",
			{ "SwitchCase": 1 }
		],
		"linebreak-style": [
			"error",
			"unix"
		],
		"quotes": [
			"error",
			"double"
		],
		"semi": [
			"error",
			"always"
		]
	}
};
