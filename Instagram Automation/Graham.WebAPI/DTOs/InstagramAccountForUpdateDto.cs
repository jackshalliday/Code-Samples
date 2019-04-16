using System.ComponentModel.DataAnnotations;

namespace Graham.WebAPI.DTOs
{
    public class InstagramAccountForUpdateDto
    {
        [Required]
        public string Username { get; set; }

        [Required]
        public string Password { get; set; }
    }
}
