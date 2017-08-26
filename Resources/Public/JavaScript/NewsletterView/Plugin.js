/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId])
/******/ 			return installedModules[moduleId].exports;
/******/
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			exports: {},
/******/ 			id: moduleId,
/******/ 			loaded: false
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.loaded = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(0);
/******/ })
/************************************************************************/
/******/ ([
/* 0 */
/***/ (function(module, exports, __webpack_require__) {

	'use strict';
	
	__webpack_require__(1);

/***/ }),
/* 1 */
/***/ (function(module, exports, __webpack_require__) {

	'use strict';
	
	var _neosUiExtensibility = __webpack_require__(2);
	
	var _neosUiExtensibility2 = _interopRequireDefault(_neosUiExtensibility);
	
	var _NewsletterView = __webpack_require__(7);
	
	var _NewsletterView2 = _interopRequireDefault(_NewsletterView);
	
	function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }
	
	(0, _neosUiExtensibility2.default)('Psmb.Newsletter:NewsletterView', {}, function (globalRegistry) {
	    var viewsRegistry = globalRegistry.get('inspector').get('views');
	
	    viewsRegistry.add('Psmb.Newsletter/Views/NewsletterView', {
	        component: _NewsletterView2.default
	    });
	});

/***/ }),
/* 2 */
/***/ (function(module, exports, __webpack_require__) {

	'use strict';
	
	Object.defineProperty(exports, "__esModule", {
	    value: true
	});
	exports.createConsumerApi = undefined;
	
	var _createConsumerApi = __webpack_require__(3);
	
	var _createConsumerApi2 = _interopRequireDefault(_createConsumerApi);
	
	var _readFromConsumerApi = __webpack_require__(6);
	
	var _readFromConsumerApi2 = _interopRequireDefault(_readFromConsumerApi);
	
	function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }
	
	exports.default = (0, _readFromConsumerApi2.default)('manifest');
	exports.createConsumerApi = _createConsumerApi2.default;

/***/ }),
/* 3 */
/***/ (function(module, exports, __webpack_require__) {

	'use strict';
	
	Object.defineProperty(exports, "__esModule", {
	    value: true
	});
	exports.default = createConsumerApi;
	
	var _package = __webpack_require__(4);
	
	var _manifest = __webpack_require__(5);
	
	var _manifest2 = _interopRequireDefault(_manifest);
	
	function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }
	
	var createReadOnlyValue = function createReadOnlyValue(value) {
	    return {
	        value: value,
	        writable: false,
	        enumerable: false,
	        configurable: true
	    };
	};
	
	function createConsumerApi(manifests, exposureMap) {
	    var api = {};
	
	    Object.keys(exposureMap).forEach(function (key) {
	        Object.defineProperty(api, key, createReadOnlyValue(exposureMap[key]));
	    });
	
	    Object.defineProperty(api, '@manifest', createReadOnlyValue((0, _manifest2.default)(manifests)));
	
	    Object.defineProperty(window, '@Neos:HostPluginAPI', createReadOnlyValue(api));
	    Object.defineProperty(window['@Neos:HostPluginAPI'], 'VERSION', createReadOnlyValue(_package.version));
	}

/***/ }),
/* 4 */
/***/ (function(module, exports) {

	module.exports = {
		"name": "@neos-project/neos-ui-extensibility",
		"version": "1.0.0-beta3",
		"description": "Extensibility mechanisms for the Neos CMS UI",
		"main": "./src/index.js",
		"scripts": {
			"prebuild": "check-dependencies && yarn clean",
			"test": "yarn jest",
			"test:watch": "yarn jest -- --watch",
			"build": "exit 0",
			"build:watch": "exit 0",
			"clean": "rimraf ./lib ./dist",
			"lint": "eslint src",
			"jest": "PWD=$(pwd) NODE_ENV=test jest -w 1 --coverage"
		},
		"dependencies": {
			"@neos-project/build-essentials": "1.0.0-beta3",
			"babel-core": "^6.13.2",
			"babel-eslint": "^7.1.1",
			"babel-loader": "^6.2.4",
			"babel-plugin-transform-decorators-legacy": "^1.3.4",
			"babel-plugin-transform-object-rest-spread": "^6.20.1",
			"babel-plugin-webpack-alias": "^2.1.1",
			"babel-preset-es2015": "^6.13.2",
			"babel-preset-react": "^6.3.13",
			"babel-preset-stage-0": "^6.3.13",
			"chalk": "^1.1.3",
			"css-loader": "^0.26.0",
			"file-loader": "^0.10.0",
			"json-loader": "^0.5.4",
			"postcss-loader": "^1.0.0",
			"react-dev-utils": "^0.5.0",
			"style-loader": "^0.13.1"
		},
		"bin": {
			"neos-react-scripts": "./bin/neos-react-scripts.js"
		},
		"jest": {
			"transformIgnorePatterns": [],
			"setupFiles": [
				"./node_modules/@neos-project/build-essentials/src/setup-browser-env.js"
			],
			"transform": {
				"neos-ui-extensibility/src/.+\\.jsx?$": "./node_modules/.bin/babel-jest",
				"node_modules/@neos-project/.+\\.jsx?$": "./node_modules/.bin/babel-jest"
			}
		}
	};

/***/ }),
/* 5 */
/***/ (function(module, exports) {

	"use strict";
	
	Object.defineProperty(exports, "__esModule", {
	    value: true
	});
	
	function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }
	
	exports.default = function (manifests) {
	    return function manifest(identifier, options, bootstrap) {
	        manifests.push(_defineProperty({}, identifier, {
	            options: options,
	            bootstrap: bootstrap
	        }));
	    };
	};

/***/ }),
/* 6 */
/***/ (function(module, exports) {

	'use strict';
	
	Object.defineProperty(exports, "__esModule", {
	    value: true
	});
	exports.default = readFromConsumerApi;
	function readFromConsumerApi(key) {
	    return function () {
	        if (window['@Neos:HostPluginAPI'] && window['@Neos:HostPluginAPI']['@' + key]) {
	            var _window$NeosHostPlu;
	
	            return (_window$NeosHostPlu = window['@Neos:HostPluginAPI'])['@' + key].apply(_window$NeosHostPlu, arguments);
	        }
	
	        throw new Error('You are trying to read from a consumer api that hasn\'t been initialized yet!');
	    };
	}

/***/ }),
/* 7 */
/***/ (function(module, exports, __webpack_require__) {

	'use strict';
	
	Object.defineProperty(exports, "__esModule", {
	    value: true
	});
	exports.default = undefined;
	
	var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();
	
	var _dec, _dec2, _class, _class2, _temp;
	
	var _react = __webpack_require__(8);
	
	var _react2 = _interopRequireDefault(_react);
	
	var _reactUiComponents = __webpack_require__(9);
	
	var _reactRedux = __webpack_require__(10);
	
	var _neosUiReduxStore = __webpack_require__(11);
	
	var _neosUiDecorators = __webpack_require__(12);
	
	var _plowJs = __webpack_require__(13);
	
	var _TestConfirmationDialog = __webpack_require__(14);
	
	var _TestConfirmationDialog2 = _interopRequireDefault(_TestConfirmationDialog);
	
	var _ConfirmationDialog = __webpack_require__(15);
	
	var _ConfirmationDialog2 = _interopRequireDefault(_ConfirmationDialog);
	
	function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }
	
	function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }
	
	function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }
	
	function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }
	
	var fetchSubscriptions = function fetchSubscriptions(nodeType) {
	    return fetch('/newsletter/getSubscriptions?nodeType=' + nodeType, {
	        credentials: 'include'
	    }).then(function (response) {
	        return response.json();
	    });
	};
	
	var _sendNewsletter = function _sendNewsletter(focusedNodeContextPath, subscription, isTest, email) {
	    var sendEndpointUrl = isTest ? '/newsletter/testSend' : '/newsletter/send';
	    var csrfToken = document.getElementById('appContainer').dataset.csrfToken;
	    var data = new URLSearchParams();
	    data.set('node', focusedNodeContextPath.replace(/user-.+\;/, 'live;'));
	    data.set('subscription', subscription);
	    data.set('__csrfToken', csrfToken);
	    if (isTest && email) {
	        data.set('email', email);
	    }
	    return fetch(sendEndpointUrl, {
	        credentials: 'include',
	        method: 'POST',
	        body: data
	    }).then(function (response) {
	        return response.json();
	    });
	};
	
	var NewsletterView = (_dec = (0, _neosUiDecorators.neos)(function (globalRegistry) {
	    return {
	        i18nRegistry: globalRegistry.get('i18n')
	    };
	}), _dec2 = (0, _reactRedux.connect)(function (state) {
	    return {
	        focusedNodeContextPath: _neosUiReduxStore.selectors.CR.Nodes.focusedNodePathSelector(state),
	        getNodeByContextPath: _neosUiReduxStore.selectors.CR.Nodes.nodeByContextPath(state)
	    };
	}), _dec(_class = _dec2(_class = (_temp = _class2 = function (_Component) {
	    _inherits(NewsletterView, _Component);
	
	    function NewsletterView(props) {
	        _classCallCheck(this, NewsletterView);
	
	        var _this = _possibleConstructorReturn(this, (NewsletterView.__proto__ || Object.getPrototypeOf(NewsletterView)).call(this, props));
	
	        _this.state = {
	            subscriptions: [],
	            selectedSubscription: null,
	            confirmationDialogIsOpen: false,
	            testConfirmationDialogIsOpen: false,
	            isError: null,
	            isSent: null
	        };
	        _this.selectSubscription = _this.selectSubscription.bind(_this);
	        _this.sendNewsletter = _this.sendNewsletter.bind(_this);
	        _this.sendTestNewsletter = _this.sendTestNewsletter.bind(_this);
	        _this.toggleConfirmationDialog = _this.toggleConfirmationDialog.bind(_this);
	        _this.toggleTestConfirmationDialog = _this.toggleTestConfirmationDialog.bind(_this);
	        return _this;
	    }
	
	    _createClass(NewsletterView, [{
	        key: 'componentDidMount',
	        value: function componentDidMount() {
	            var _this2 = this;
	
	            var node = this.props.getNodeByContextPath(this.props.focusedNodeContextPath);
	            var nodeType = (0, _plowJs.$get)('nodeType', node);
	            if (nodeType) {
	                fetchSubscriptions(nodeType).then(function (json) {
	                    return _this2.setState({ subscriptions: json });
	                });
	            }
	        }
	    }, {
	        key: 'toggleConfirmationDialog',
	        value: function toggleConfirmationDialog(isOpen) {
	            this.setState({ confirmationDialogIsOpen: isOpen });
	        }
	    }, {
	        key: 'toggleTestConfirmationDialog',
	        value: function toggleTestConfirmationDialog(isOpen) {
	            this.setState({ testConfirmationDialogIsOpen: isOpen });
	        }
	    }, {
	        key: 'selectSubscription',
	        value: function selectSubscription(value) {
	            this.setState({ selectedSubscription: value });
	        }
	    }, {
	        key: 'sendNewsletter',
	        value: function sendNewsletter() {
	            var _this3 = this;
	
	            var isTest = false;
	            _sendNewsletter(this.props.focusedNodeContextPath, this.state.selectedSubscription, isTest).then(function (json) {
	                console.log('asdf', json);
	                return json.status === 'success' ? _this3.setState({ isSent: true }) : _this3.setState({ isError: true });
	            }).catch(function () {
	                return _this3.setState({ isError: true });
	            });
	            this.toggleConfirmationDialog(false);
	        }
	    }, {
	        key: 'sendTestNewsletter',
	        value: function sendTestNewsletter(email) {
	            var _this4 = this;
	
	            var isTest = true;
	            _sendNewsletter(this.props.focusedNodeContextPath, this.state.selectedSubscription, isTest, email).then(function (json) {
	                console.log('asdf1', json);
	                return json.status === 'success' ? _this4.setState({ isSent: true }) : _this4.setState({ isError: true });
	            }).catch(function () {
	                return _this4.setState({ isError: true });
	            });
	            this.toggleTestConfirmationDialog(false);
	        }
	    }, {
	        key: 'render',
	        value: function render() {
	            var _this5 = this;
	
	            return _react2.default.createElement(
	                'div',
	                null,
	                _react2.default.createElement(_reactUiComponents.SelectBox, {
	                    value: this.state.selectedSubscription,
	                    options: this.state.subscriptions,
	                    onValueChange: this.selectSubscription
	                }),
	                _react2.default.createElement(
	                    _reactUiComponents.Button,
	                    { disabled: !this.state.selectedSubscription, style: 'brand', onClick: function onClick() {
	                            return _this5.toggleConfirmationDialog(true);
	                        } },
	                    this.props.i18nRegistry.translate('Psmb.Newsletter:Main:js.send')
	                ),
	                _react2.default.createElement(
	                    _reactUiComponents.Button,
	                    { disabled: !this.state.selectedSubscription, style: 'clean', onClick: function onClick() {
	                            return _this5.toggleTestConfirmationDialog(true);
	                        } },
	                    this.props.i18nRegistry.translate('Psmb.Newsletter:Main:js.test')
	                ),
	                this.state.isError ? _react2.default.createElement(
	                    'div',
	                    { style: { marginTop: '16px', color: 'red' } },
	                    this.props.i18nRegistry.translate('Psmb.Newsletter:Main:js.error')
	                ) : '',
	                this.state.isSent ? _react2.default.createElement(
	                    'div',
	                    { style: { marginTop: '16px', color: 'green' } },
	                    this.props.i18nRegistry.translate('Psmb.Newsletter:Main:js.sent')
	                ) : '',
	                _react2.default.createElement(_TestConfirmationDialog2.default, {
	                    isOpen: this.state.testConfirmationDialogIsOpen,
	                    translate: this.props.i18nRegistry.translate.bind(this.props.i18nRegistry),
	                    close: function close() {
	                        return _this5.toggleTestConfirmationDialog(false);
	                    },
	                    send: this.sendTestNewsletter
	                }),
	                _react2.default.createElement(_ConfirmationDialog2.default, {
	                    isOpen: this.state.confirmationDialogIsOpen,
	                    translate: this.props.i18nRegistry.translate.bind(this.props.i18nRegistry),
	                    close: function close() {
	                        return _this5.toggleConfirmationDialog(false);
	                    },
	                    send: this.sendNewsletter
	                })
	            );
	        }
	    }]);
	
	    return NewsletterView;
	}(_react.Component), _class2.propTypes = {
	    focusedNodeContextPath: _react.PropTypes.string,
	    getNodeByContextPath: _react.PropTypes.func.isRequired
	}, _temp)) || _class) || _class);
	exports.default = NewsletterView;

/***/ }),
/* 8 */
/***/ (function(module, exports, __webpack_require__) {

	'use strict';
	
	var _readFromConsumerApi = __webpack_require__(6);
	
	var _readFromConsumerApi2 = _interopRequireDefault(_readFromConsumerApi);
	
	function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }
	
	module.exports = (0, _readFromConsumerApi2.default)('vendor')().React;

/***/ }),
/* 9 */
/***/ (function(module, exports, __webpack_require__) {

	'use strict';
	
	var _readFromConsumerApi = __webpack_require__(6);
	
	var _readFromConsumerApi2 = _interopRequireDefault(_readFromConsumerApi);
	
	function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }
	
	module.exports = (0, _readFromConsumerApi2.default)('NeosProjectPackages')().ReactUiComponents;

/***/ }),
/* 10 */
/***/ (function(module, exports, __webpack_require__) {

	'use strict';
	
	var _readFromConsumerApi = __webpack_require__(6);
	
	var _readFromConsumerApi2 = _interopRequireDefault(_readFromConsumerApi);
	
	function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }
	
	module.exports = (0, _readFromConsumerApi2.default)('vendor')().reactRedux;

/***/ }),
/* 11 */
/***/ (function(module, exports, __webpack_require__) {

	'use strict';
	
	var _readFromConsumerApi = __webpack_require__(6);
	
	var _readFromConsumerApi2 = _interopRequireDefault(_readFromConsumerApi);
	
	function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }
	
	module.exports = (0, _readFromConsumerApi2.default)('NeosProjectPackages')().NeosUiReduxStore;

/***/ }),
/* 12 */
/***/ (function(module, exports, __webpack_require__) {

	'use strict';
	
	var _readFromConsumerApi = __webpack_require__(6);
	
	var _readFromConsumerApi2 = _interopRequireDefault(_readFromConsumerApi);
	
	function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }
	
	module.exports = (0, _readFromConsumerApi2.default)('NeosProjectPackages')().NeosUiDecorators;

/***/ }),
/* 13 */
/***/ (function(module, exports, __webpack_require__) {

	'use strict';
	
	var _readFromConsumerApi = __webpack_require__(6);
	
	var _readFromConsumerApi2 = _interopRequireDefault(_readFromConsumerApi);
	
	function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }
	
	module.exports = (0, _readFromConsumerApi2.default)('vendor')().plow;

/***/ }),
/* 14 */
/***/ (function(module, exports, __webpack_require__) {

	'use strict';
	
	Object.defineProperty(exports, "__esModule", {
	    value: true
	});
	exports.default = undefined;
	
	var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();
	
	var _class, _temp;
	
	var _react = __webpack_require__(8);
	
	var _react2 = _interopRequireDefault(_react);
	
	var _reactUiComponents = __webpack_require__(9);
	
	function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }
	
	function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }
	
	function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }
	
	function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }
	
	var TestConfirmationDialog = (_temp = _class = function (_Component) {
	    _inherits(TestConfirmationDialog, _Component);
	
	    function TestConfirmationDialog(props) {
	        _classCallCheck(this, TestConfirmationDialog);
	
	        var _this = _possibleConstructorReturn(this, (TestConfirmationDialog.__proto__ || Object.getPrototypeOf(TestConfirmationDialog)).call(this, props));
	
	        _this.state = {
	            email: ''
	        };
	        return _this;
	    }
	
	    _createClass(TestConfirmationDialog, [{
	        key: 'render',
	        value: function render() {
	            var _this2 = this;
	
	            var _props = this.props,
	                isOpen = _props.isOpen,
	                translate = _props.translate,
	                close = _props.close,
	                send = _props.send;
	
	            return _react2.default.createElement(
	                _reactUiComponents.Dialog,
	                {
	                    isOpen: isOpen,
	                    title: translate('Psmb.Newsletter:Main:js.testConfirmationTitle'),
	                    onRequestClose: close,
	                    actions: [_react2.default.createElement(
	                        _reactUiComponents.Button,
	                        { onClick: close, style: 'clean' },
	                        translate('Neos.Neos:Main:cancel')
	                    ), _react2.default.createElement(
	                        _reactUiComponents.Button,
	                        { disabled: !this.state.email.includes('@'), onClick: function onClick() {
	                                return send(_this2.state.email);
	                            }, style: 'brand' },
	                        translate('Psmb.Newsletter:Main:js.send')
	                    )]
	                },
	                _react2.default.createElement(
	                    'div',
	                    { style: { padding: '16px' } },
	                    translate('Psmb.Newsletter:Main:js.testEmailLabel'),
	                    _react2.default.createElement(_reactUiComponents.TextInput, {
	                        onChange: function onChange(email) {
	                            return _this2.setState({ email: email });
	                        },
	                        value: this.state.email
	                    })
	                )
	            );
	        }
	    }]);
	
	    return TestConfirmationDialog;
	}(_react.Component), _class.propTypes = {
	    isOpen: _react.PropTypes.bool,
	    translate: _react.PropTypes.func.isRequired,
	    close: _react.PropTypes.func.isRequired,
	    send: _react.PropTypes.func.isRequired
	}, _temp);
	exports.default = TestConfirmationDialog;

/***/ }),
/* 15 */
/***/ (function(module, exports, __webpack_require__) {

	'use strict';
	
	Object.defineProperty(exports, "__esModule", {
	    value: true
	});
	
	var _react = __webpack_require__(8);
	
	var _react2 = _interopRequireDefault(_react);
	
	var _reactUiComponents = __webpack_require__(9);
	
	function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }
	
	var ConfirmationDialog = function ConfirmationDialog(_ref) {
	    var isOpen = _ref.isOpen,
	        translate = _ref.translate,
	        close = _ref.close,
	        send = _ref.send;
	
	    return _react2.default.createElement(
	        _reactUiComponents.Dialog,
	        {
	            isOpen: isOpen,
	            title: translate('Psmb.Newsletter:Main:js.testConfirmationTitle'),
	            onRequestClose: close,
	            actions: [_react2.default.createElement(
	                _reactUiComponents.Button,
	                { onClick: close, style: 'clean' },
	                translate('Neos.Neos:Main:cancel')
	            ), _react2.default.createElement(
	                _reactUiComponents.Button,
	                { onClick: send, style: 'brand' },
	                translate('Psmb.Newsletter:Main:js.send')
	            )]
	        },
	        _react2.default.createElement(
	            'div',
	            { style: { padding: '16px' } },
	            translate('Psmb.Newsletter:Main:js.confirmationDescription')
	        )
	    );
	};
	ConfirmationDialog.propTypes = {
	    isOpen: _react.PropTypes.bool,
	    translate: _react.PropTypes.func.isRequired,
	    close: _react.PropTypes.func.isRequired,
	    send: _react.PropTypes.func.isRequired
	};
	
	exports.default = ConfirmationDialog;

/***/ })
/******/ ]);
//# sourceMappingURL=Plugin.js.map