module.exports = {
	main_php: {
				src: [ '<%= pkg.pot.src %>' ],
				overwrite: true,
				replacements: [{
					from: /Version:\s*(.*)/,
					to: "Version: <%= pkg.version %>"
				},{
					from: /VERSION', \s*(.*)/,
					to: "VERSION', '<%= pkg.version %>' );"
				}]
			}
		};