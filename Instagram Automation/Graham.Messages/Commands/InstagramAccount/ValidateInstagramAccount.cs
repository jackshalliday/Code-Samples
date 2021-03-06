﻿using NServiceBus;

namespace Graham.Messages.Commands.InstagramAccount
{
    public class ValidateInstagramAccount : ICommand
    {
        public long InstagramAccountId { get; set; }
        public string Username { get; set; }
        public string Password { get; set; }
    }
}
