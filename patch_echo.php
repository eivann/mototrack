<?php
$content = file_get_contents('resources/views/layouts/app.blade.php');
$target = 'init() {';
$replacement = <<<EOD
init() {
                            document.addEventListener('DOMContentLoaded', () => {
                                setTimeout(() => {
                                    if (window.Echo) {
                                        window.Echo.private(`App.Models.User.{{\auth()->id()}}`)
                                            .notification((notification) => {
                                                this.addToast({
                                                    id: notification.id || Date.now(),
                                                    data: {
                                                        title: notification.title,
                                                        message: notification.message,
                                                        link: notification.link
                                                    }
                                                });
                                            });
                                    }
                                }, 1000);
                            });
EOD;
$content = str_replace($target, $replacement, $content);
file_put_contents('resources/views/layouts/app.blade.php', $content);
echo "Replaced!";
